<?php

namespace App\Http\Requests;

use App\Models\KonfigurasiJamKerja;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKonfigurasiJamKerjaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tujuh hari harus lengkap dan berbeda; jam kerja boleh kosong untuk
     * hari libur pegawai.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jadwal' => ['required', 'array', 'size:7'],
            'jadwal.*.hari' => ['required', Rule::in(KonfigurasiJamKerja::HARI), 'distinct'],
            'jadwal.*.id_jam_kerja' => ['nullable', 'integer', Rule::exists('jam_kerja', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jadwal.required' => 'Jadwal wajib diisi.',
            'jadwal.size' => 'Jadwal harus tepat tujuh hari.',
            'jadwal.*.hari.required' => 'Hari wajib diisi.',
            'jadwal.*.hari.in' => 'Hari tidak dikenal.',
            'jadwal.*.hari.distinct' => 'Hari tidak boleh berulang.',
            'jadwal.*.id_jam_kerja.integer' => 'Jam kerja tidak valid.',
            'jadwal.*.id_jam_kerja.exists' => 'Jam kerja tidak ditemukan.',
        ];
    }
}
