<?php

namespace App\Filament\Resources\DegreeProgramResource\Pages;

use App\Filament\Resources\DegreeProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDegreePrograms extends ListRecords
{
    protected static string $resource = DegreeProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
