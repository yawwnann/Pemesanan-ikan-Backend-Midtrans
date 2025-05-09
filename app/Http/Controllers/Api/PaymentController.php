<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan; // Pastikan Anda punya model Pesanan
// use App\Models\User;    // Tidak secara langsung dipakai di sini, tapi Pesanan punya relasi ke User
use Midtrans\Snap;      // Impor Snap dari Midtrans SDK
use Midtrans\Notification; // Impor Notification dari Midtrans SDK
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Untuk logging
use Illuminate\Support\Facades\DB;  // Untuk database transactions

class PaymentController extends Controller
{
    public function initiatePayment(Request $request, Pesanan $pesanan)
    {
        // (Kode initiatePayment Anda dari sebelumnya tetap di sini)
        // Pastikan logika ini sudah benar dan sesuai dengan PesananService jika Anda memindahkannya
        if ($pesanan->status_pembayaran === 'paid' || $pesanan->status_pembayaran === 'settlement') {
            return response()->json(['error' => 'Pesanan ini sudah dibayar.'], 400);
        }

        $midtransOrderId = $pesanan->midtrans_order_id;
        if (!$midtransOrderId || !in_array($pesanan->status_pembayaran, ['paid', 'settlement', 'capture'])) {
            $midtransOrderId = 'ORDER-' . $pesanan->id . '-' . time();
            $pesanan->midtrans_order_id = $midtransOrderId;
            $pesanan->status_pembayaran = 'pending';
            $pesanan->save();
        }

        $item_details = [];
        if ($pesanan->items && $pesanan->items->isNotEmpty()) {
            foreach ($pesanan->items as $item) {
                $item_details[] = [
                    'id' => (string) $item->id,
                    'price' => (int) $item->pivot->harga_saat_pesan,
                    'quantity' => (int) $item->pivot->jumlah,
                    'name' => Str::limit($item->nama_ikan ?? $item->nama, 50, '...'), // Cek nama_ikan dulu, fallback ke nama
                ];
            }
        } else {
            $item_details[] = [
                'id' => 'PESANAN-' . $pesanan->id,
                'price' => (int) $pesanan->total_harga,
                'quantity' => 1,
                'name' => 'Total Pesanan #' . $pesanan->id,
            ];
        }

        $calculated_gross_amount = 0;
        foreach ($item_details as $detail) {
            $calculated_gross_amount += $detail['price'] * $detail['quantity'];
        }
        $gross_amount_to_send = $calculated_gross_amount > 0 ? $calculated_gross_amount : (int) $pesanan->total_harga;
        if ($gross_amount_to_send !== (int) $pesanan->total_harga && !empty($pesanan->items) && $pesanan->items->isNotEmpty()) {
            Log::warning("Perbedaan gross_amount untuk Order ID [{$midtransOrderId}]: Kalkulasi item_details [{$calculated_gross_amount}], total_harga pesanan [{$pesanan->total_harga}]. Menggunakan hasil kalkulasi item_details.");
        }


        $customer_details = [
            'first_name' => $pesanan->nama_pelanggan,
            'last_name' => '',
            'email' => $pesanan->user && $pesanan->user->email ? $pesanan->user->email : ('guest-' . $pesanan->id . '@pasifix.com'),
            'phone' => $pesanan->nomor_whatsapp,
        ];

        $transaction_details = [
            'order_id' => $midtransOrderId,
            'gross_amount' => $gross_amount_to_send,
        ];

        $params = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json([
                'snap_token' => $snapToken,
                'order_id' => $midtransOrderId,
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Token Exception for Order ID [' . $midtransOrderId . ']: ' . $e->getMessage() . "\nParams: " . json_encode($params));
            return response()->json(['error' => 'Gagal memulai pembayaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle Midtrans HTTP Notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleNotification(Request $request)
    {
        $notificationPayload = $request->getContent();
        Log::info('Midtrans Notification Received Raw: ' . $notificationPayload);

        try {
            // Buat instance Notification. Library akan mem-parse JSON dari php://input
            // dan melakukan verifikasi signature dasar jika Server Key dikonfigurasi global.
            $notification = new Notification();

            // Di dalam PaymentController@handleNotification
            $transactionStatus = $notification->transaction_status ?? null;
            $paymentType = $notification->payment_type ?? null;
            $orderId = $notification->order_id ?? null;
            $fraudStatus = $notification->fraud_status ?? null;
            $transactionId = $notification->transaction_id ?? null;

            Log::info("Midtrans Notification Parsed: Order ID [{$orderId}], Transaction ID [{$transactionId}], Status [{$transactionStatus}], Payment Type [{$paymentType}], Fraud [{$fraudStatus}]");

            // Jika order_id tidak ada di payload, ini bukan notifikasi pembayaran standar yang kita harapkan
            if (!$orderId) {
                Log::warning('Midtrans Notification: order_id tidak ditemukan dalam payload.', (array) $notification);
                // Kirim 200 OK agar Midtrans tidak mengirim ulang notifikasi yang tidak relevan/tidak lengkap ini
                return response()->json(['message' => 'Notifikasi diterima tetapi tidak mengandung order_id yang valid.'], 200);
            }
            // Jika transaction_status tidak ada, ini mungkin juga bukan notifikasi pembayaran standar
            if (!$transactionStatus) {
                Log::warning("Midtrans Notification: transaction_status tidak ditemukan untuk Order ID [{$orderId}]. Mungkin jenis notifikasi lain.", (array) $notification);
                return response()->json(['message' => 'Notifikasi diterima tetapi tidak mengandung transaction_status.'], 200);
            }


            $pesanan = Pesanan::where('midtrans_order_id', $orderId)->first();

            if (!$pesanan) {
                Log::warning("Midtrans Notification: Pesanan dengan midtrans_order_id [{$orderId}] tidak ditemukan. Notifikasi diabaikan.");
                return response()->json(['message' => 'Pesanan tidak ditemukan.'], 200);
            }

            // Idempotency: Jika status sudah final sukses, jangan proses lagi untuk status sukses yang sama
            $finalSuccessStatuses = ['paid', 'settlement', 'capture'];
            if (
                in_array($pesanan->status_pembayaran, $finalSuccessStatuses) &&
                ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept'))
            ) {
                Log::info("Midtrans Notification: Pesanan [{$orderId}] sudah dalam status sukses final [{$pesanan->status_pembayaran}]. Notifikasi [{$transactionStatus}] diabaikan.");
                return response()->json(['message' => 'Pesanan sudah diproses sebelumnya.'], 200);
            }

            DB::beginTransaction();
            try {
                $originalStatusPembayaran = $pesanan->status_pembayaran;

                // Selalu update transaction_id jika ada & berbeda (atau belum ada)
                if ($transactionId && $pesanan->midtrans_transaction_id !== $transactionId) {
                    $pesanan->midtrans_transaction_id = $transactionId;
                }
                // Selalu update metode pembayaran jika ada & berbeda (atau belum ada)
                if ($paymentType && $pesanan->metode_pembayaran !== $paymentType) {
                    $pesanan->metode_pembayaran = $paymentType;
                }

                $newStatusPembayaran = $pesanan->status_pembayaran; // Inisialisasi dengan status saat ini

                if ($transactionStatus == 'capture') {
                    if ($fraudStatus == 'challenge') {
                        $newStatusPembayaran = 'challenge';
                    } else if ($fraudStatus == 'accept') {
                        $newStatusPembayaran = 'paid'; // Dianggap lunas
                        // TODO: Logika bisnis setelah lunas (misal: update status pesanan utama, kirim email, kurangi stok)
                    } else if ($fraudStatus == 'deny') {
                        $newStatusPembayaran = 'failed'; // Atau 'denied'
                    }
                } else if ($transactionStatus == 'settlement') {
                    $newStatusPembayaran = 'paid'; // Dianggap lunas
                    // TODO: Logika bisnis setelah lunas
                } else if ($transactionStatus == 'pending') {
                    $newStatusPembayaran = 'pending';
                } else if ($transactionStatus == 'deny') {
                    $newStatusPembayaran = 'failed'; // Atau 'denied'
                } else if ($transactionStatus == 'expire') {
                    $newStatusPembayaran = 'expired';
                } else if ($transactionStatus == 'cancel') {
                    $newStatusPembayaran = 'cancelled';
                }
                // Default jika transactionStatus tidak dikenali, mungkin biarkan status pembayaran tetap?
                // Atau set ke status tertentu jika perlu. Untuk saat ini, tidak diubah.

                // Hanya simpan jika status pembayaran benar-benar berubah
                if ($originalStatusPembayaran !== $newStatusPembayaran || $pesanan->isDirty(['midtrans_transaction_id', 'metode_pembayaran'])) {
                    $pesanan->status_pembayaran = $newStatusPembayaran; // Terapkan status baru
                    $pesanan->save();
                    Log::info("Pesanan [{$orderId}] status pembayaran diupdate dari [{$originalStatusPembayaran}] menjadi [{$pesanan->status_pembayaran}]. Metode: {$pesanan->metode_pembayaran}. TransID: {$pesanan->midtrans_transaction_id}.");
                } else {
                    Log::info("Pesanan [{$orderId}] status pembayaran tidak diubah karena sudah [{$originalStatusPembayaran}] atau notifikasi duplikat tanpa perubahan data relevan.");
                }

                DB::commit();
                return response()->json(['message' => 'Notifikasi berhasil diproses.'], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Midtrans Notification DB Processing Error for Order ID [{$orderId}]: " . $e->getMessage() . " Stack trace: " . $e->getTraceAsString());
                // Kirim 500 jika terjadi error saat proses DB agar Midtrans bisa mencoba lagi
                return response()->json(['message' => 'Kesalahan internal saat memproses notifikasi.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Midtrans Notification Handling Error (Initial Parse/Auth): ' . $e->getMessage() . " Raw Payload: " . $notificationPayload . " Stack: " . $e->getTraceAsString());
            // Gagal mem-parse notifikasi awal atau masalah signature. Kirim 400.
            return response()->json(['message' => 'Gagal memproses notifikasi: format tidak valid atau masalah autentikasi.'], 400);
        }
    }
}