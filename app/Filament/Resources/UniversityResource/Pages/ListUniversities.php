<?php

namespace App\Filament\Resources\UniversityResource\Pages;

use App\Filament\Concerns\ScoutTableSearch;
use App\Filament\Resources\UniversityResource;
use App\Models\University;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUniversities extends ListRecords
{
    use ScoutTableSearch;

    protected static string $resource = UniversityResource::class;

    protected function scoutModel(): string
    {
        return University::class;
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
