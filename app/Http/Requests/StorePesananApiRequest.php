<?php

// File: app/Http/Requests/StorePesananApiRequest.php

namespace App\Http\Requests; // Pastikan namespace ini benar

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // Atau bisa gunakan $this->user() nanti

class StorePesananApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Izinkan jika user sudah login (ditangani middleware Sanctum)
        return Auth::check();
        // atau return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Validasi untuk data yang DIKIRIM dari frontend
        return [
            'nama_pelanggan' => [
                'required',
                'string',
                'max:255',
                // Tambahkan validasi cek keranjang di sini menggunakan Closure
                function ($attribute, $value, $fail) {
                    $user = $this->user(); // Dapatkan user yang terotentikasi
                    if (!$user) {
                        $fail('Autentikasi pengguna gagal.'); // Middleware seharusnya sudah menangani ini
                        return;
                    }
                    // --- PERIKSA KERANJANG ---
                    // Ganti 'keranjangItems' dengan nama relasi yang BENAR di model User Anda
                    // yang menghubungkan User ke item keranjangnya.
                    if ($user->keranjangItems()->doesntExist()) { // Cek apakah keranjang kosong
                        $fail('Minimal ada satu item ikan yang dipesan.'); // Pesan error jika kosong
                    }
                }
            ],
            'nomor_whatsapp' => ['required', 'string', 'max:20', 'regex:/^[0-9\-\+\s\(\)]+$/'], // Jadikan required (sesuai frontend)
            'alamat_pengiriman' => ['required', 'string', 'max:1000'], // Jadikan required (sesuai frontend)
            'catatan' => ['nullable', 'string', 'max:1000'],

            // --- HAPUS VALIDASI UNTUK INPUT 'items' ---
            // 'items' => ['required', 'array', 'min:1'], // <-- HAPUS/Komentari
            // 'items.*.ikan_id' => ['required', 'integer', 'exists:ikan,id'], // <-- HAPUS/Komentari
            // 'items.*.jumlah' => ['required', 'integer', 'min:1'], // <-- HAPUS/Komentari
        ];
    }

    /**
     * Custom message for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama penerima wajib diisi.',
            'nomor_whatsapp.required' => 'Nomor HP wajib diisi.',
            'nomor_whatsapp.regex' => 'Format nomor HP tidak valid.',
            'alamat_pengiriman.required' => 'Alamat pengiriman wajib diisi.',
            // Pesan untuk closure rule akan diambil dari $fail() di atas.
            // Hapus pesan untuk validasi 'items' yang sudah dihapus.
            // 'items.required' => 'Minimal ada satu item ikan yang dipesan.',
            // 'items.min' => 'Minimal ada satu item ikan yang dipesan.',
            // 'items.*.ikan_id.required' => 'ID Ikan wajib dipilih untuk setiap item.',
            // 'items.*.ikan_id.exists' => 'ID Ikan yang dipilih tidak valid.',
            // 'items.*.jumlah.required' => 'Jumlah wajib diisi untuk setiap item.',
            // 'items.*.jumlah.min' => 'Jumlah minimal adalah 1 untuk setiap item.',
        ];
    }

    // Method prepareForValidation() tidak diperlukan jika closure rule
    // ditempelkan pada field yang sudah ada seperti 'nama_pelanggan'.
}