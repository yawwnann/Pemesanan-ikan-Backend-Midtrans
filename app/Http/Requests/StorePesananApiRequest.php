<?php
// File: app/Http/Requests/StorePesananApiRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePesananApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Atur ke true jika API ini publik atau otentikasi ditangani di middleware route
     */
    public function authorize(): bool
    {
        return true; // Ubah jika perlu otorisasi spesifik
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:255'],
            'nomor_whatsapp' => ['nullable', 'string', 'max:20'],
            'alamat_pengiriman' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'], // Wajib ada item, minimal 1
            'items.*.ikan_id' => ['required', 'integer', 'exists:ikan,id'], // Setiap item harus punya ikan_id yg valid di tabel ikan
            'items.*.jumlah' => ['required', 'integer', 'min:1'], // Setiap item harus punya jumlah minimal 1
        ];
    }

    /**
     * Custom message for validation errors.
     * Opsional: Pesan error kustom dalam Bahasa Indonesia
     * @return array
     */
    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'items.required' => 'Minimal ada satu item ikan yang dipesan.',
            'items.min' => 'Minimal ada satu item ikan yang dipesan.',
            'items.*.ikan_id.required' => 'ID Ikan wajib dipilih untuk setiap item.',
            'items.*.ikan_id.exists' => 'ID Ikan yang dipilih tidak valid.',
            'items.*.jumlah.required' => 'Jumlah wajib diisi untuk setiap item.',
            'items.*.jumlah.min' => 'Jumlah minimal adalah 1 untuk setiap item.',
        ];
    }
}