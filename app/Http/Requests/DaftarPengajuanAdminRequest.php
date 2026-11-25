<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DaftarPengajuanAdminRequest extends FormRequest
{
    /**
     * Route admin dan Policy viewAny sudah membatasi akses; request ini hanya
     * memvalidasi filter.
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
            'id_pegawai' => ['nullable', 'integer', 'exists:pegawai,id'],
            'tanggal_dari' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_dari'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Status pengajuan tidak valid.',
            'id_pegawai.integer' => 'Pegawai tidak valid.',
            'id_pegawai.exists' => 'Pegawai tidak ditemukan.',
            'tanggal_dari.date_format' => 'Format tanggal awal harus YYYY-MM-DD.',
            'tanggal_sampai.date_format' => 'Format tanggal akhir harus YYYY-MM-DD.',
            'tanggal_sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }
}
