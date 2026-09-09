<?php

namespace App\Filament\Resources\CityGuideResource\Pages;

use App\Filament\Resources\CityGuideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCityGuide extends EditRecord
{
    protected static string $resource = CityGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
