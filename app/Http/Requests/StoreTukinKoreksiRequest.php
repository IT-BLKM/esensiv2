<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTukinKoreksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_pegawai' => ['required', 'integer', 'exists:pegawai,id'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'persen' => ['required', 'numeric', 'between:-100,100', 'not_in:0'],
            'alasan' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_pegawai.required' => 'Pegawai wajib dipilih.',
            'id_pegawai.exists' => 'Pegawai tidak ditemukan.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.between' => 'Tahun tidak valid.',
            'bulan.required' => 'Bulan wajib diisi.',
            'bulan.between' => 'Bulan tidak valid.',
            'persen.required' => 'Persentase koreksi wajib diisi.',
            'persen.numeric' => 'Persentase koreksi harus berupa angka.',
            'persen.between' => 'Persentase koreksi harus di antara -100 dan 100.',
            'persen.not_in' => 'Persentase koreksi tidak boleh nol.',
            'alasan.required' => 'Alasan koreksi wajib diisi.',
            'alasan.max' => 'Alasan maksimal 255 karakter.',
        ];
    }
}
