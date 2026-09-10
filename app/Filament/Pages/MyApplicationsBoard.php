<?php

namespace App\Filament\Pages;

use App\Models\ShortlistItem;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Kanban view of the student's shortlist — one column per application
 * status, cards dragged between columns to change status and within a
 * column to reorder (persisted on shortlist_items.sort_order, shared with
 * the My Applications list). Open to every panel user; no HasPageShield.
 */
class MyApplicationsBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = 'Applications Board';

    protected static ?string $title = 'Applications Board';

    protected static ?string $slug = 'applications-board';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.my-applications-board';

    /** @var Collection<int, ShortlistItem>|null */
    protected ?Collection $itemsCache = null;

    protected function items(): Collection
    {
        return $this->itemsCache ??= auth()->user()->shortlistItems()
            ->with(['degreeProgram.university'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return array<int, array{
     *     status: string, label: string, color: string,
     *     cards: array<int, array{id: int, university: string, program: string, logo: string, tier: ?string, tier_color: ?string}>,
     * }>
     */
    public function getBoard(): array
    {
        $byStatus = $this->items()->groupBy('status');

        $columns = [];
        foreach (ShortlistItem::STATUSES as $status => $label) {
            $columns[] = [
                'status' => $status,
                'label' => $label,
                'color' => ShortlistItem::STATUS_COLORS[$status] ?? 'gray',
                'cards' => ($byStatus[$status] ?? collect())
                    ->map(fn (ShortlistItem $item) => [
                        'id' => $item->id,
                        'university' => $item->degreeProgram->university->display_name,
                        'program' => $item->degreeProgram->name,
                        'logo' => $item->degreeProgram->university->display_logo_url,
                        'tier' => $item->tierLabel(),
                        'tier_color' => $item->tier ? (ShortlistItem::TIER_COLORS[$item->tier] ?? 'gray') : null,
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return $columns;
    }

    /**
     * Move a card to a status column, inserting it before $beforeId (or at
     * the end when null). Re-sequences sort_order for the affected column so
     * the order sticks.
     */
    public function moveCard(int $itemId, string $toStatus, ?int $beforeId = null): void
    {
        if (! array_key_exists($toStatus, ShortlistItem::STATUSES)) {
            return;
        }

        $item = auth()->user()->shortlistItems()->whereKey($itemId)->first();

        if ($item === null) {
            return;
        }

        $this->itemsCache = null;

        // Target column, without the moved card, as a plain id list.
        $ids = $this->items()
            ->where('status', $toStatus)
            ->pluck('id')
            ->reject(fn (int $id) => $id === $itemId)
            ->values()
            ->all();

        $at = $beforeId !== null ? array_search($beforeId, $ids, true) : false;

        if ($at === false) {
            $ids[] = $itemId;
        } else {
            array_splice($ids, $at, 0, [$itemId]);
        }

        $item->status = $toStatus;
        $item->save();

        foreach ($ids as $index => $id) {
            ShortlistItem::whereKey($id)->update(['sort_order' => $index]);
        }

        $this->itemsCache = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('List view')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(MyApplications::getUrl()),
        ];
    }
}
