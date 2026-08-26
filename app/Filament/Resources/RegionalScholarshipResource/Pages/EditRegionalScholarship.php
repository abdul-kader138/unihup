<?php

namespace App\Filament\Resources\RegionalScholarshipResource\Pages;

use App\Filament\Resources\RegionalScholarshipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRegionalScholarship extends EditRecord
{
    protected static string $resource = RegionalScholarshipResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
