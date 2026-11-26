<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TukinRequest extends FormRequest
{
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
            'id_pegawai' => ['nullable', 'integer', 'exists:pegawai,id'],
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
            'id_pegawai.integer' => 'Pegawai tidak valid.',
            'id_pegawai.exists' => 'Pegawai tidak ditemukan.',
            'bulan.integer' => 'Bulan harus berupa angka.',
            'bulan.between' => 'Bulan harus berada di antara 1 dan 12.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.between' => 'Tahun tidak valid.',
        ];
    }
}
