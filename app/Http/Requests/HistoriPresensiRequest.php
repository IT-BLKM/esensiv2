<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class HistoriPresensiRequest extends FormRequest
{
    /**
     * Halaman histori hanya menampilkan data milik user yang login; route
     * `role:pegawai` sudah membatasi aksesnya.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filter dikirim lewat query string GET. Semua nilai divalidasi sebagai
     * integer sebelum menyentuh query, jadi tidak ada yang masuk ke SQL.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bulan.integer' => 'Bulan harus berupa angka.',
            'bulan.between' => 'Bulan harus berada di antara 1 dan 12.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.between' => 'Tahun tidak valid.',
        ];
    }
}
