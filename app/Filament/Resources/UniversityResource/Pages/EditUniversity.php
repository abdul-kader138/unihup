<?php

namespace App\Filament\Resources\UniversityResource\Pages;

use App\Filament\Resources\UniversityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUniversity extends EditRecord
{
    protected static string $resource = UniversityResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
