<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DaftarPengajuanRequest extends FormRequest
{
    /**
     * Halaman daftar pengajuan hanya menampilkan milik user login; route
     * `role:pegawai` dan Policy sudah membatasi aksesnya.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
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
            'status.in' => 'Status pengajuan tidak valid.',
            'bulan.integer' => 'Bulan harus berupa angka.',
            'bulan.between' => 'Bulan harus berada di antara 1 dan 12.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.between' => 'Tahun tidak valid.',
        ];
    }
}
