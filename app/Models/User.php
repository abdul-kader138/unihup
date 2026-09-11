<?php

namespace App\Models;

use App\Notifications\Auth\ResetPassword;
use App\Notifications\Auth\VerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['first_name', 'last_name', 'email', 'phone', 'marketing_opt_in', 'whatsapp_number', 'whatsapp_opt_in', 'whatsapp_opt_in_at', 'password', 'avatar', 'google_id', 'email_verified_at', 'preferred_subject_id', 'preferred_degree_level', 'nationality', 'is_eu_citizen', 'prior_education_country', 'english_level', 'italian_level', 'scholarship_interest', 'home_currency', 'study_profile_completed_at', 'deadline_reminders_opt_out', 'weekly_digest_sent_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    use \Illuminate\Auth\MustVerifyEmail;

    // Tracks dispatch only on this instance; later resend requests remain available.
    protected bool $verificationNotificationDispatched = false;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('user');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'marketing_opt_in' => 'boolean',
            'whatsapp_opt_in' => 'boolean',
            'whatsapp_opt_in_at' => 'datetime',
            'is_eu_citizen' => 'boolean',
            'scholarship_interest' => 'boolean',
            'study_profile_completed_at' => 'datetime',
            'deadline_reminders_opt_out' => 'boolean',
            'weekly_digest_sent_at' => 'datetime',
            'journey_last_active_date' => 'date',
            // Encrypted at rest — plain Eloquent casts, no extra package needed.
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // Computed, not stored — `name` is split into first_name/last_name
    // columns, but plenty of framework code (Notifiable's default "Hello"
    // greeting, activity log display, etc.) still expects a `name` attribute
    // to just work.
    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'panel_user'])
            || $this->getAllPermissions()->isNotEmpty();
    }

    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return filled($this->two_factor_secret) && filled($this->two_factor_confirmed_at);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function sendEmailVerificationNotification(): void
    {
        Log::info('verification.dispatch_requested', [
            'user_id' => $this->getKey(),
            'queue_connection' => config('queue.default'),
        ]);

        $this->notify(new VerifyEmail);

        $this->verificationNotificationDispatched = true;

        Log::info('verification.dispatched', ['user_id' => $this->getKey()]);
    }

    public function hasDispatchedVerificationNotification(): bool
    {
        return $this->verificationNotificationDispatched;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function preferredSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'preferred_subject_id');
    }

    public function whatsappConversation(): HasOne
    {
        return $this->hasOne(WhatsAppConversation::class);
    }

    public function shortlistItems(): HasMany
    {
        return $this->hasMany(ShortlistItem::class);
    }

    public function shortlistedPrograms(): BelongsToMany
    {
        return $this->belongsToMany(DegreeProgram::class, 'shortlist_items')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function journeyProgress(): HasMany
    {
        return $this->hasMany(JourneyProgress::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function hasCompletedStudyProfile(): bool
    {
        return $this->study_profile_completed_at !== null;
    }

    /**
     * Bump the "opened My Journey today" streak. Safe to call more than once
     * a day — a second call the same day is a no-op.
     */
    public function recordJourneyActivity(): void
    {
        $today = now()->toDateString();
        $last = $this->journey_last_active_date?->toDateString();

        if ($last === $today) {
            return;
        }

        $this->journey_streak_current = $last === now()->subDay()->toDateString()
            ? $this->journey_streak_current + 1
            : 1;

        $this->journey_streak_longest = max($this->journey_streak_longest, $this->journey_streak_current);
        $this->journey_last_active_date = $today;
        $this->save();
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_user_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->referral_code ??= self::generateUniqueReferralCode();
        });
    }

    private static function generateUniqueReferralCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = Str::upper(Str::random(8));

            if (! self::where('referral_code', $code)->exists()) {
                return $code;
            }
        }

        // Astronomically unlikely to be reached, but keep registration from
        // ever hard-failing on a collision streak.
        return Str::upper(Str::random(12));
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }
}
