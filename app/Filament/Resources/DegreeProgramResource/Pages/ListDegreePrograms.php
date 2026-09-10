<?php

namespace App\Filament\Resources\DegreeProgramResource\Pages;

use App\Filament\Concerns\ScoutTableSearch;
use App\Filament\Resources\DegreeProgramResource;
use App\Models\DegreeProgram;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDegreePrograms extends ListRecords
{
    use ScoutTableSearch;

    protected static string $resource = DegreeProgramResource::class;

    protected function scoutModel(): string
    {
        return DegreeProgram::class;
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
