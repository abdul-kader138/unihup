<?php

namespace App\Filament\Resources\CityGuideResource\Pages;

use App\Filament\Resources\CityGuideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCityGuides extends ListRecords
{
    protected static string $resource = CityGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
