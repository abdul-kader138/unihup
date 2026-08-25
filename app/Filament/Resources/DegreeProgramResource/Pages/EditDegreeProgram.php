<?php

namespace App\Filament\Resources\DegreeProgramResource\Pages;

use App\Filament\Resources\DegreeProgramResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDegreeProgram extends EditRecord
{
    protected static string $resource = DegreeProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
