<?php

namespace App\Filament\Resources\IkanResource\Pages;

use App\Filament\Resources\IkanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIkan extends EditRecord
{
    protected static string $resource = IkanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
