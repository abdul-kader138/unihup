<?php

namespace App\Filament\Resources\DeadlineResource\Pages;

use App\Filament\Resources\DeadlineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeadlines extends ListRecords
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
