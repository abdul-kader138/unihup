<?php

namespace App\Filament\Pages;

use App\Models\CityGuide;
use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use App\Models\University;
use App\Support\CostOfLivingCopy;
use App\Support\ItalianRegions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * A student-facing overview of one university: ranking, city, every program
 * it offers (with a save-to-list toggle), its deadlines, and cross-links to
 * the city guide and regional scholarships. Reached from the university name
 * on Find Universities and from the program detail modal (?id= or ?slug=).
 * Open to every panel user; no HasPageShield, hidden from the sidebar.
 */
class UniversityProfile extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'university';

    protected static ?string $title = 'University';

    protected static string $view = 'filament.pages.university-profile';

    public ?University $university = null;

    public function mount(): void
    {
        $id = request()->integer('id');
        $slug = trim((string) request()->query('slug'));

        $this->university = University::query()
            ->when($id > 0, fn ($q) => $q->whereKey($id))
            ->when($id <= 0 && $slug !== '', fn ($q) => $q->where('slug', $slug))
            ->with('rankings')
            ->first();

        abort_unless($this->university !== null, 404);
    }

    public function getTitle(): string
    {
        return $this->university?->display_name ?? 'University';
    }

    /**
     * @return Collection<string, Collection<int, DegreeProgram>>
     */
    public function getProgramsByLevel(): Collection
    {
        return $this->university->degreePrograms()
            ->with('subject')
            ->orderBy('name')
            ->get()
            ->groupBy('degree_level');
    }

    /**
     * @return array<int, int>
     */
    public function getShortlistedProgramIds(): array
    {
        return auth()->user()->shortlistItems()
            ->whereIn('degree_program_id', $this->university->degreePrograms()->select('id'))
            ->pluck('degree_program_id')
            ->all();
    }

    public function toggleShortlist(int $programId): void
    {
        $program = $this->university->degreePrograms()->whereKey($programId)->first();

        if ($program === null) {
            return;
        }

        $existing = auth()->user()->shortlistItems()
            ->where('degree_program_id', $programId)
            ->first();

        if ($existing !== null) {
            $existing->delete();
            $this->university->decrement('shortlist_items_count');
            Notification::make()->title('Removed from your list')->send();

            return;
        }

        auth()->user()->shortlistItems()->create([
            'degree_program_id' => $programId,
            'status' => 'researching',
        ]);
        $this->university->increment('shortlist_items_count');

        Notification::make()->title('Saved to your list')->success()->send();
    }

    public function getCityGuide(): ?CityGuide
    {
        return CityGuide::forCity($this->university->city);
    }

    /**
     * @return Collection<int, RegionalScholarship>
     */
    public function getRegionalScholarships(): Collection
    {
        $regionKey = ItalianRegions::canonicalize($this->university->region);

        if ($regionKey === null) {
            return collect();
        }

        return RegionalScholarship::all()
            ->filter(fn (RegionalScholarship $s) => ItalianRegions::canonicalize($s->region) === $regionKey)
            ->values();
    }

    /**
     * @return array{rent: int, tier: string}|null
     */
    public function getCostOfLiving(): ?array
    {
        return CostOfLivingCopy::forCity($this->university->city);
    }

    /**
     * @return Collection<int, Deadline>
     */
    public function getUpcomingDeadlines(): Collection
    {
        $programIds = $this->university->degreePrograms()->pluck('id');

        return Deadline::query()
            ->active()
            ->upcoming()
            ->where(function ($q) use ($programIds) {
                $q->where(fn ($s) => $s->where('scope_type', Deadline::SCOPE_UNIVERSITY)->where('scope_id', $this->university->id))
                    ->orWhere(fn ($s) => $s->where('scope_type', Deadline::SCOPE_PROGRAM)->whereIn('scope_id', $programIds));
            })
            ->orderBy('due_at')
            ->limit(8)
            ->get();
    }
}
