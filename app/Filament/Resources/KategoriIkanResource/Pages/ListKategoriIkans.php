<?php

namespace App\Filament\Resources\KategoriIkanResource\Pages;

use App\Filament\Resources\KategoriIkanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKategoriIkans extends ListRecords
{
    protected static string $resource = KategoriIkanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
