<?php
// File: app/Filament/Resources/PesananResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\PesananResource\Pages;
use App\Filament\Resources\PesananResource\RelationManagers;
use App\Models\Pesanan;
use App\Models\Ikan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn; // Pastikan ini diimport
use Filament\Tables\Columns\IconColumn; // Import ini juga jika dipakai di tempat lain atau untuk testing
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;

class PesananResource extends Resource
{
    protected static ?string $model = Pesanan::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $modelLabel = 'Pesanan';
    protected static ?string $pluralModelLabel = 'Manajemen Pesanan';
    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_pelanggan')->required()->maxLength(255),
                TextInput::make('nomor_whatsapp')->label('Nomor WhatsApp')->tel()->maxLength(20)->required(),
                DatePicker::make('tanggal_pesan')->label('Tanggal Pesan')->default(now()),

                Repeater::make('items')
                    ->label('Item Ikan Dipesan')
                    ->schema([
                        Select::make('ikan_id')
                            ->label('Ikan')
                            ->options(Ikan::query()->where('stok', '>', 0)->pluck('nama_ikan', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $ikan = Ikan::find($state);
                                $set('harga_saat_pesan', $ikan?->harga ?? 0);
                            })
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->columnSpan(4),
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->reactive()
                            ->columnSpan(2),
                        TextInput::make('harga_saat_pesan')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->readOnly()
                            ->columnSpan(2),
                    ])
                    ->columns(8)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Ikan Lain')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateTotalPrice($get, $set);
                    })
                    ->deleteAction(
                        fn(Get $get, Set $set) => self::updateTotalPrice($get, $set),
                    )
                    ->columnSpanFull(),

                TextInput::make('total_harga')
                    ->label('Total Keseluruhan')
                    ->numeric()
                    ->prefix('Rp')
                    ->readOnly(),
                // ->required(), // Tetap tidak required

                Select::make('status')
                    ->label('Status Pesanan')
                    ->options([
                        'Baru' => 'Baru',
                        'Diproses' => 'Diproses',
                        'Dikirim' => 'Dikirim',
                        'Selesai' => 'Selesai',
                        'Batal' => 'Batal',
                    ])
                    ->required()
                    ->default('Baru'),

                Textarea::make('catatan')
                    ->label('Catatan Admin')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function updateTotalPrice(Get $get, Set $set): void
    {
        $items = $get('items');
        $total = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $jumlah = $item['jumlah'] ?? 0;
                $harga = $item['harga_saat_pesan'] ?? 0;
                if (!empty($jumlah) && !empty($harga)) { // Pengecekan ditambahkan
                    $total += $jumlah * $harga;
                }
            }
        }
        $set('total_harga', $total);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_pesan')->date()->sortable(),
                TextColumn::make('nama_pelanggan')->searchable()->sortable(),
                TextColumn::make('nomor_whatsapp')->searchable(),

                // === KEMBALIKAN KOLOM INI KE VERSI YANG BENAR ===
                TextColumn::make('items_list') // Nama kolomnya bisa bebas
                    ->label('Item Dipesan')
                    ->formatStateUsing(function ($record) {
                        $record->loadMissing('items');
                        if ($record->items->isEmpty()) {
                            return '-';
                        }
                        return $record->items->map(function ($ikan) {
                            // Pastikan relasi items di Pesanan pakai ->withPivot('jumlah')
                            return $ikan->nama_ikan . ' (' . ($ikan->pivot->jumlah ?? '?') . ')';
                        })->implode(', ');
                    })
                    ->limit(50)
                    ->tooltip(function ($record) {
                        $record->loadMissing('items');
                        if ($record->items->isEmpty()) {
                            return null;
                        }
                        return $record->items->map(function ($ikan) {
                            return $ikan->nama_ikan . ' (' . ($ikan->pivot->jumlah ?? '?') . ')';
                        })->implode("\n");
                    }),
                // ==============================================

                TextColumn::make('total_harga')->money('IDR')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Baru' => 'warning',
                        'Diproses' => 'primary',
                        'Dikirim' => 'info',
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([ /* ... opsi filter status ... */])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPesanans::route('/'),
            'create' => Pages\CreatePesanan::route('/create'),
            'edit' => Pages\EditPesanan::route('/{record}/edit'),
        ];
    }
}