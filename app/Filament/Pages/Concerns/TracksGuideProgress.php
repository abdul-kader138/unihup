<?php

namespace App\Filament\Pages\Concerns;

use App\Models\JourneyProgress;
use Illuminate\Support\Str;

// Section keys come from each guide's own copy class (a stable `key` per
// section, or `step-N` for the visa guide) — see the consuming page's
// sectionKeys().

/**
 * Lets a curated guide page (Admission Tests, Doc Recognition, Visa &
 * Arrival) track which sections a student has ticked off. Progress is stored
 * in App\Models\JourneyProgress under keys like "guide:visa-arrival:step-3",
 * so it lives alongside the main journey checklist and shows on My Journey.
 *
 * The consuming page must implement guideKey() and sectionKeys().
 */
trait TracksGuideProgress
{
    abstract public function guideKey(): string;

    /** @return array<int, string> every section key this guide can track */
    abstract public function sectionKeys(): array;

    protected function progressKey(string $sectionKey): string
    {
        return "guide:{$this->guideKey()}:{$sectionKey}";
    }

    public function toggleGuideSection(string $sectionKey): void
    {
        if (! in_array($sectionKey, $this->sectionKeys(), true)) {
            return;
        }

        $row = JourneyProgress::firstOrNew([
            'user_id' => auth()->id(),
            'step_key' => $this->progressKey($sectionKey),
        ]);

        if ($row->state === JourneyProgress::STATE_DONE) {
            $row->state = JourneyProgress::STATE_PENDING;
            $row->completed_at = null;
        } else {
            $row->state = JourneyProgress::STATE_DONE;
            $row->completed_at = now();
        }

        $row->save();
    }

    /**
     * @return array<string, bool> section key => done
     */
    public function guideSectionStates(): array
    {
        $done = JourneyProgress::query()
            ->where('user_id', auth()->id())
            ->where('state', JourneyProgress::STATE_DONE)
            ->where('step_key', 'like', "guide:{$this->guideKey()}:%")
            ->pluck('step_key')
            ->map(fn (string $k) => Str::afterLast($k, ':'))
            ->flip();

        $states = [];
        foreach ($this->sectionKeys() as $key) {
            $states[$key] = isset($done[$key]);
        }

        return $states;
    }

    /**
     * @return array{done: int, total: int, percent: int}
     */
    public function guideProgress(): array
    {
        $states = $this->guideSectionStates();
        $total = count($states);
        $done = count(array_filter($states));

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }
}
