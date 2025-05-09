<?php

// File: app/Filament/Resources/PesananResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\PesananResource\Pages;
use App\Models\Pesanan;
use App\Models\Ikan;
use App\Models\KategoriIkan; // Pastikan model ini ada jika filter dipakai
use App\Models\User;
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
// use Filament\Tables\Columns\SelectColumn; // Tidak terpakai
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select as FormSelect;
// use Filament\Forms\Components\Placeholder; // Tidak terpakai di versi ini

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
                // Kolom 1: Detail Pelanggan & Pesanan
                Forms\Components\Group::make()
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
                            ->required()
                            ->columnSpanFull(),
                        Select::make('user_id')
                            ->label('User Terdaftar (Opsional)')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->placeholder('Pilih User jika pesanan dari user terdaftar')
                            ->helperText('Kosongkan jika pesanan bukan dari user terdaftar.'),
                        DatePicker::make('tanggal_pesan')
                            ->label('Tanggal Pesan')
                            ->default(now())
                            ->readOnly(),
                        Textarea::make('catatan')
                            ->label('Catatan Pelanggan')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columnSpan(['lg' => 2]),

                // Kolom 2: Status & Pembayaran
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status & Pembayaran')
                            ->schema([
                                Select::make('status')
                                    ->label('Status Pesanan')
                                    ->options([ /* Opsi status... */])
                                    ->required()
                                    ->default('Baru'),
                                Select::make('status_pembayaran')
                                    ->label('Status Pembayaran')
                                    ->options([ /* Opsi status bayar... */])
                                    ->helperText('Status dari Payment Gateway')
                                    ->required(),
                                TextInput::make('metode_pembayaran')
                                    ->label('Metode Pembayaran')
                                    ->readOnly()
                                    ->placeholder('-'),
                                TextInput::make('midtrans_order_id')
                                    ->label('Midtrans Order ID')
                                    ->readOnly()
                                    ->placeholder('-')
                                    ->helperText('ID unik yang dikirim ke Midtrans'),
                                TextInput::make('midtrans_transaction_id')
                                    ->label('Midtrans Transaction ID')
                                    ->readOnly()
                                    ->placeholder('-')
                                    ->helperText('ID unik transaksi dari Midtrans'),
                            ])
                    ])->columnSpan(['lg' => 1]),

                // Repeater Item di bawah, full width
                Repeater::make('items')
                    ->label('Item Ikan Dipesan')
                    ->relationship() // <-- Memberitahu Repeater untuk mengelola relasi 'items'
                    ->schema([
                        // --- PERBAIKAN SELECT IKAN_ID ---
                        Select::make('ikan_id') // Langsung bind ke foreign key di tabel pivot
                            ->label('Ikan')
                            // Sediakan SEMUA ikan sebagai pilihan
                            ->options(Ikan::query()->pluck('nama_ikan', 'id')->toArray())
                            ->required()
                            ->reactive() // Agar bisa update harga saat ikan dipilih
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                // Set harga_saat_pesan berdasarkan ikan yang dipilih
                                $ikan = Ikan::find($state);
                                $set('harga_saat_pesan', $ikan?->harga ?? 0);
                            })
                            ->searchable() // Aktifkan pencarian pada opsi
                            // ->distinct() // Sebaiknya dihapus jika pakai relationship repeater
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems() // Biarkan jika perlu
                            ->columnSpan([
                                'md' => 4,
                            ]),
                        // --- AKHIR PERBAIKAN ---

                        TextInput::make('jumlah') // Bind ke kolom 'jumlah' di pivot
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->reactive() // Agar total harga terupdate live
                            ->columnSpan([
                                'md' => 2,
                            ]),
                        TextInput::make('harga_saat_pesan') // Bind ke kolom 'harga_saat_pesan' di pivot
                            ->label('Harga Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            // ->readOnly() // Sebaiknya readOnly atau disabled
                            ->disabled()   // Lebih cocok disabled, karena nilainya diset oleh Select ikan_id
                            ->columnSpan([
                                'md' => 2,
                            ]),
                    ])
                    ->columns(8)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Item Ikan')
                    ->live(debounce: 500) // Update total harga saat repeater berubah
                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotalPrice($get, $set))
                    ->deleteAction(
                        fn(Forms\Components\Actions\Action $action) => $action->after(fn(Get $get, Set $set) => self::updateTotalPrice($get, $set)),
                    )
                    // ->reorderable(false) // Uncomment jika tidak ingin item bisa diubah urutannya
                    ->columnSpanFull(),

                TextInput::make('total_harga')
                    ->label('Total Keseluruhan')
                    ->numeric()
                    ->prefix('Rp')
                    ->readOnly(), // Dihitung otomatis

            ])->columns(3);
    }

    // Helper function untuk update total harga live (tetap sama)
    public static function updateTotalPrice(Get $get, Set $set): void
    {
        $items = $get('items');
        $total = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                // Ambil jumlah dan harga dari state repeater saat ini
                $jumlah = $item['jumlah'] ?? 0;
                $harga = $item['harga_saat_pesan'] ?? 0; // Gunakan harga yang tersimpan di repeater item
                if (!empty($jumlah) && is_numeric($jumlah) && !empty($harga) && is_numeric($harga)) {
                    $total += $jumlah * $harga;
                }
            }
        }
        $set('total_harga', $total);
    }

    // Method table() dan lainnya tetap sama seperti versi sebelumnya...
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_pesan')->date()->sortable(),
                TextColumn::make('nama_pelanggan')->searchable()->sortable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_harga')->money('IDR')->sortable(),
                TextColumn::make('status_pembayaran')
                    ->label('Status Bayar')->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'pending' => 'warning',
                        'paid', 'settlement', 'capture' => 'success',
                        'challenge' => 'info',
                        'failure', 'failed', 'deny', 'cancel', 'cancelled', 'expire', 'expired' => 'danger',
                        default => 'gray',
                    })->searchable()->sortable(),
                TextColumn::make('metode_pembayaran')
                    ->label('Metode Bayar')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status Pesanan')->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Baru' => 'warning', 'Diproses' => 'primary', 'Dikirim' => 'info',
                        'Selesai' => 'success', 'Batal' => 'danger', default => 'gray',
                    })->searchable()->sortable(),
                TextColumn::make('nomor_whatsapp')->label('No. WA')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat Pada')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([ /* Opsi status ... */]),
                SelectFilter::make('status_pembayaran')->label('Status Pembayaran')->options([ /* Opsi status bayar ... */]),
                SelectFilter::make('metode_pembayaran')->label('Metode Pembayaran')->options([ /* Opsi metode bayar ... */]),
                Filter::make('tanggal_pesan') // Filter tanggal
                    ->form([
                        DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')->label('Sampai Tanggal')->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari_tanggal'], fn(Builder $query, $date): Builder => $query->whereDate('tanggal_pesan', '>=', $date))
                            ->when($data['sampai_tanggal'], fn(Builder $query, $date): Builder => $query->whereDate('tanggal_pesan', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat')->color('gray'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]), // Kosongkan jika tidak perlu bulk action
            ])
            ->defaultSort('tanggal_pesan', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
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