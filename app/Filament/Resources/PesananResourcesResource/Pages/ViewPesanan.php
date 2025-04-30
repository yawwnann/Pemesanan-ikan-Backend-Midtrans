<?php
// File: app/Filament/Resources/PesananResource/Pages/ViewPesanan.php

namespace App\Filament\Resources\PesananResource\Pages;

use App\Filament\Resources\PesananResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewPesanan extends ViewRecord
{
    protected static string $resource = PesananResource::class;

    // Method untuk mendefinisikan tampilan Infolist
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informasi Pelanggan')
                    ->schema([
                        Components\TextEntry::make('nama_pelanggan'),
                        Components\TextEntry::make('nomor_whatsapp')->label('Nomor WhatsApp'),
                    ])->columns(2),
                Components\Section::make('Detail Pesanan')
                    ->schema([
                        Components\TextEntry::make('tanggal_pesan')->date(),
                        Components\TextEntry::make('total_harga')->money('IDR'),
                        Components\TextEntry::make('status')->badge()->color(fn(string $state): string => match ($state) {
                            'Baru' => 'warning',
                            'Diproses' => 'primary',
                            'Dikirim' => 'info',
                            'Selesai' => 'success',
                            'Batal' => 'danger',
                            default => 'gray',
                        }),
                        Components\TextEntry::make('catatan')->columnSpanFull(),
                    ])->columns(3),
                Components\Section::make('Item Ikan Dipesan')
                    ->schema([
                        // Menampilkan item dari relasi many-to-many
                        Components\RepeatableEntry::make('items') // <-- Gunakan RepeatableEntry untuk relasi items
                            ->label('') // Kosongkan label utama jika tidak perlu
                            ->schema([
                                Components\TextEntry::make('nama_ikan') // Ambil nama dari model Ikan terkait
                                    ->label('Nama Ikan')
                                    ->inlineLabel() // Label di samping
                                    ->weight('bold'),
                                Components\TextEntry::make('pivot.jumlah') // Ambil jumlah dari pivot
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->inlineLabel(),
                                Components\TextEntry::make('pivot.harga_saat_pesan') // Ambil harga dari pivot
                                    ->label('Harga Satuan')
                                    ->money('IDR')
                                    ->inlineLabel(),
                            ])
                            ->columns(3) // Atur layout per item
                            ->grid(2), // Tampilkan 2 item per baris grid
                    ])

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetakPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                // Mengarahkan ke route 'pesanan.pdf' dengan parameter ID pesanan saat ini
                ->url(fn() => route('pesanan.pdf', ['pesanan' => $this->record]))
                // Membuka URL di tab baru
                ->openUrlInNewTab(),

            // Mungkin tambahkan tombol Edit juga jika perlu
            Actions\EditAction::make(),
        ];
    }
}