<?php

namespace App\Filament\Resources\PenyewaanResource\Pages;

use App\Filament\Resources\PenyewaanResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPenyewaan extends EditRecord
{
    protected static string $resource = PenyewaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(PenyewaanResource::getUrl('index')),

            DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}