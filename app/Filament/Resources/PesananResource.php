<?php
// File: app/Filament/Resources/PesananResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\PesananResource\Pages;
use App\Models\Pesanan;
use App\Models\Ikan;
use App\Models\KategoriIkan;
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
use Filament\Tables\Columns\SelectColumn; // Untuk status editable
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select as FormSelect; // Alias untuk Select di form filter


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
                TextInput::make('nama_pelanggan')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nomor_whatsapp')
                    ->label('Nomor WhatsApp')
                    ->tel()
                    ->maxLength(20)
                    ->required(),
                Textarea::make('alamat_pengiriman')
                    ->label('Alamat Pengiriman')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->label('User Terdaftar (Opsional)')
                    ->relationship('user', 'name') // Relasi ke 'user', tampilkan 'name'
                    ->searchable()
                    ->placeholder('Pilih User jika pesanan dari user terdaftar')
                    ->helperText('Kosongkan jika pesanan bukan dari user terdaftar.'),
                DatePicker::make('tanggal_pesan')
                    ->label('Tanggal Pesan')
                    ->default(now()),

                Repeater::make('items')
                    ->label('Item Ikan Dipesan')
                    // ->relationship() // Tidak pakai ini, handle manual di Pages
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
                    ->live(debounce: 500) // Aktifkan live update total
                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotalPrice($get, $set))
                    ->deleteAction(fn(Get $get, Set $set) => self::updateTotalPrice($get, $set))
                    ->columnSpanFull(),

                TextInput::make('total_harga')
                    ->label('Total Keseluruhan')
                    ->numeric()
                    ->prefix('Rp')
                    ->readOnly(), // readOnly, nilai dihitung otomatis

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
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    // Helper function untuk update total harga live
    public static function updateTotalPrice(Get $get, Set $set): void
    {
        $items = $get('items');
        $total = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $jumlah = $item['jumlah'] ?? 0;
                $harga = $item['harga_saat_pesan'] ?? 0;
                if (!empty($jumlah) && is_numeric($jumlah) && !empty($harga) && is_numeric($harga)) {
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
                TextColumn::make('tanggal_pesan')
                    ->date()
                    ->sortable(), // Sortable
                TextColumn::make('nama_pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nomor_whatsapp')
                    ->searchable(),
                TextColumn::make('total_harga')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status') // Tambahkan label jika perlu
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Baru' => 'warning',
                        'Diproses' => 'primary',
                        'Dikirim' => 'info',
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('alamat_pengiriman') // Kolom alamat (opsional)
                    ->label('Alamat')
                    ->limit(40)
                    ->tooltip(fn($state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([ /* ... opsi status ... */]),
                Filter::make('kategori_ikan')
                    ->form([
                        FormSelect::make('kategori_id')
                            ->label('Kategori Ikan')
                            ->options(KategoriIkan::pluck('nama_kategori', 'id'))
                            ->placeholder('Semua Kategori'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['kategori_id'],
                            fn(Builder $query, $kategoriId): Builder =>
                            $query->whereHas(
                                'items',
                                fn(Builder $q) =>
                                $q->where('kategori_id', $kategoriId)
                            )
                        );
                    }),
                Filter::make('tanggal_pesan')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_pesan', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_pesan', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Details') // Ubah label
                    ->color('success'),  // Ubah warna
                Tables\Actions\EditAction::make()
                    ->color('success'),  // Ubah warna (opsional)
                Tables\Actions\DeleteAction::make()
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Bisa tambahkan BulkAction ubah status di sini jika perlu
                ]),
            ])
            ->defaultSort('tanggal_pesan', 'desc'); // Urutkan berdasarkan tanggal pesan terbaru
    }

    public static function getRelations(): array
    {
        return []; // Kosongkan jika tidak ada relation manager
    }

    public static function getPages(): array
    {
        // Pastikan ini mengarah ke Class Page yang benar
        return [
            'index' => Pages\ListPesanans::route('/'),
            'create' => Pages\CreatePesanan::route('/create'),
            'edit' => Pages\EditPesanan::route('/{record}/edit'),
            'view' => Pages\ViewPesanan::route('/{record}'),
        ];
    }
}