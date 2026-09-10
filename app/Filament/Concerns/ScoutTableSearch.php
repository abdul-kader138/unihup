<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Routes a Filament table's global search box through Laravel Scout when a
 * real engine is configured (Meilisearch/Typesense/collection), so one box
 * matches across every field in the model's toSearchableArray and, on a
 * real engine, tolerates typos. Falls back to Filament's per-column LIKE
 * search when SCOUT_DRIVER is null (e.g. in tests).
 *
 * The consuming page/resource-page must implement scoutModel() returning a
 * model class that uses Laravel\Scout\Searchable.
 */
trait ScoutTableSearch
{
    /** @return class-string<Model> */
    abstract protected function scoutModel(): string;

    protected function applyGlobalSearchToTableQuery(Builder $query): Builder
    {
        $term = trim((string) $this->getTableSearch());
        $scoutReady = ! in_array(config('scout.driver'), [null, '', 'null'], true);

        if ($term === '' || ! $scoutReady) {
            return $this->applyColumnLikeSearch($query, $term);
        }

        $model = $this->scoutModel();

        return $query->whereIn(
            $query->getModel()->getQualifiedKeyName(),
            $model::search($term)->keys()->all(),
        );
    }

    /**
     * Filament's default per-column LIKE search, inlined because the trait
     * method it lives in can't be reached via parent::.
     */
    private function applyColumnLikeSearch(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        foreach ($this->extractTableSearchWords($term) as $word) {
            $query->where(function (Builder $query) use ($word) {
                $isFirst = true;

                foreach ($this->getTable()->getColumns() as $column) {
                    if ($column->isHidden() || ! $column->isGloballySearchable()) {
                        continue;
                    }

                    $column->applySearchConstraint($query, $word, $isFirst);
                }
            });
        }

        return $query;
    }
}
