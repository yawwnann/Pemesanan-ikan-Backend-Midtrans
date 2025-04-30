<?php
// File: app/Filament/Resources/PesananResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\PesananResource\Pages;
use App\Models\Pesanan;
use App\Models\Ikan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection; // Mungkin tidak perlu jika tidak ada komputasi Collection manual

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
                    // ->relationship()
                    ->schema([
                        Select::make('ikan_id')
                            ->label('Ikan')

                            ->options(Ikan::query()->where('stok', '>', 0)->pluck('nama_ikan', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                $ikan = Ikan::find($state);
                                \Illuminate\Support\Facades\Log::info(
                                    'Ikan found in afterStateUpdated:',
                                    $ikan ? $ikan->toArray() : ['selected_id' => $state, 'result' => 'NOT FOUND']
                                );
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
                        TextInput::make('harga_saat_pesan') // Kolom Pivot
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
                    ->live(debounce: 500) // Tetap aktifkan untuk update total
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
                    ->readOnly()
                // ->required() // Sebaiknya tidak required di form, dihitung saja
                ,

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

    // Helper function untuk update total harga
    public static function updateTotalPrice(Get $get, Set $set): void
    {
        $items = $get('items');
        $total = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $jumlah = $item['jumlah'] ?? 0;
                $harga = $item['harga_saat_pesan'] ?? 0;
                if (!empty($jumlah) && !empty($harga)) {
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
                    ->options([
                        'Baru' => 'Baru',
                        'Diproses' => 'Diproses',
                        'Dikirim' => 'Dikirim',
                        'Selesai' => 'Selesai',
                        'Batal' => 'Batal',
                    ])
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('success'),
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
            'view' => Pages\ViewPesanan::route('/{record}'),
        ];
    }
}