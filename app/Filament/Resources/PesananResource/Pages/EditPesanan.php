<?php
// File: app/Filament/Resources/PesananResource/Pages/EditPesanan.php

namespace App\Filament\Resources\PesananResource\Pages;

use App\Filament\Resources\PesananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model; // <-- Import Model
use Illuminate\Support\Arr;           // <-- Import Arr facade
use Illuminate\Support\Facades\DB;    // <-- Import DB facade
use Illuminate\Support\Facades\Log;    // <-- Import Log jika perlu debug

class EditPesanan extends EditRecord
{
    protected static string $resource = PesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Method ini untuk memuat data ke form saat edit (sudah kita buat sebelumnya)
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $pesananRecord = $this->record;
        $pesananRecord->loadMissing('items');
        $itemsData = [];
        foreach ($pesananRecord->items as $ikanItem) {
            $itemsData[] = [
                'ikan_id' => $ikanItem->id,
                'jumlah' => $ikanItem->pivot->jumlah,
                'harga_saat_pesan' => $ikanItem->pivot->harga_saat_pesan
            ];
        }
        $data['items'] = $itemsData;

        // Kalkulasi ulang total harga saat load
        $total = 0;
        foreach ($itemsData as $item) {
            $jumlah = $item['jumlah'] ?? 0;
            $harga = $item['harga_saat_pesan'] ?? 0;
            if (!empty($jumlah) && !empty($harga)) {
                $total += $jumlah * $harga;
            }
        }
        $data['total_harga'] = $total;

        // Log::info('Data Pesanan SEBELUM Fill (Edit):', $data);
        return $data;
    }

    // --- TAMBAHKAN METHOD INI UNTUK HANDLE SIMPAN EDIT ---
    /**
     * Handle the saving of the record after form submission.
     *
     * @param  Model  $record The existing record being edited.
     * @param  array  $data   The validated form data.
     * @return Model The updated record.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Log::info('--- handleRecordUpdate START for Pesanan ID: ' . $record->id . ' ---');
        // Log::info('Received Data for Update:', $data);

        // Bungkus dalam transaksi database
        return DB::transaction(function () use ($record, $data) {
            // 1. Pisahkan data utama dan data items
            $itemsData = $data['items'] ?? [];
            $pesananData = Arr::except($data, ['items']);

            // 2. Hitung ulang total harga dari data items yang disubmit
            $total = 0;
            $pivotData = []; // Siapkan data untuk sync
            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $jumlah = $item['jumlah'] ?? 0;
                    $harga = $item['harga_saat_pesan'] ?? 0;
                    $ikanId = $item['ikan_id'] ?? null;

                    if ($ikanId && $jumlah > 0) {
                        $total += $jumlah * $harga;
                        // Siapkan format data untuk method sync()
                        $pivotData[$ikanId] = [
                            'jumlah' => $jumlah,
                            'harga_saat_pesan' => $harga,
                        ];
                    }
                }
            }
            $pesananData['total_harga'] = $total; // Set total harga baru
            // Log::info('Calculated Total Price for Update:', ['total_harga' => $total]);
            // Log::info('Pivot Data Prepared for Sync (Update):', $pivotData);

            try {
                // 3. Update data utama Pesanan
                // Log::info('Attempting to update Pesanan record ID: ' . $record->id);
                $record->update($pesananData);
                // Log::info('Pesanan Record Updated:', ['id' => $record->id]);

                // 4. Sinkronkan relasi items ke tabel pivot
                // Log::info('Attempting to sync items for Pesanan ID: ' . $record->id);
                // sync() akan otomatis menambah, mengupdate, atau menghapus
                // record di tabel pivot sesuai $pivotData
                $record->items()->sync($pivotData);
                // Log::info('Sync completed for Pesanan ID: ' . $record->id);

                // Log::info('--- handleRecordUpdate returning updated record ---');
                return $record; // Kembalikan record yang sudah diupdate

            } catch (\Exception $e) {
                Log::error('Error during handleRecordUpdate transaction for Pesanan ID: ' . $record->id . ' - ' . $e->getMessage(), ['exception' => $e]);
                throw $e; // Lemparkan lagi error setelah dicatat
            }
        });
    }
    // --- AKHIR METHOD HANDLE SIMPAN EDIT ---
}