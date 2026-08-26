<?php

namespace App\Filament\Resources\RegionalScholarshipResource\Pages;

use App\Filament\Resources\RegionalScholarshipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRegionalScholarships extends ListRecords
{
    protected static string $resource = RegionalScholarshipResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
