<?php

namespace App\Filament\Resources\PenyewaanResource\Pages;

use App\Filament\Resources\PenyewaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPenyewaans extends ListRecords
{
    protected static string $resource = PenyewaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input Data'),
        ];
    }
}