<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * A student's personal referral link and the friends who've joined through
 * it. Track-only for now — no credits/rewards ledger exists yet; the payoff
 * today is a Filament bell notification to the referrer (see
 * App\Filament\Auth\Register::attributeReferral()) plus this running tally.
 */
class MyReferrals extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Invite Friends';

    protected static ?string $title = 'Invite Friends';

    protected static ?string $slug = 'my-referrals';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.my-referrals';

    public function getReferralLink(): string
    {
        return url('/r/'.auth()->user()->referral_code);
    }

    /**
     * @return Collection<int, User>
     */
    public function getReferredUsers(): Collection
    {
        return auth()->user()->referredUsers()
            ->select(['id', 'first_name', 'last_name', 'created_at'])
            ->latest()
            ->get();
    }
}
