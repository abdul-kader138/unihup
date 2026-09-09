<?php

namespace App\Filament\Resources\DeadlineResource\Pages;

use App\Filament\Resources\DeadlineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeadline extends EditRecord
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
