<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePengajuanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Hanya field yang boleh diubah pegawai. `id_pegawai`, `status`, dan
     * jejak approval sengaja tidak diterima dari klien (anti-IDOR).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_cuti' => [
                'nullable',
                'integer',
                'required_if:jenis,cuti',
                'exists:cuti,id',
                'prohibited_if:jenis,sakit,dl',
            ],
            'mode_dl' => [
                'nullable',
                Rule::in(['full', 'setengah']),
                'required_if:jenis,dl',
                'prohibited_if:jenis,cuti,sakit',
            ],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_berakhir' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'jenis' => ['required', Rule::in(['cuti', 'sakit', 'dl'])],
            'keterangan' => ['required', 'string', 'max:255'],
            'lampiran' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_cuti.required_if' => 'Jenis cuti wajib memilih jenis cuti.',
            'id_cuti.exists' => 'Jenis cuti tidak ditemukan.',
            'id_cuti.prohibited_if' => 'Jenis sakit dan dl tidak boleh memilih jenis cuti.',
            'mode_dl.in' => 'Mode Dinas Luar tidak valid.',
            'mode_dl.required_if' => 'Mode Dinas Luar wajib dipilih untuk pengajuan Dinas Luar.',
            'mode_dl.prohibited_if' => 'Mode Dinas Luar hanya berlaku untuk pengajuan Dinas Luar.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date_format' => 'Format tanggal mulai harus YYYY-MM-DD.',
            'tanggal_berakhir.date_format' => 'Format tanggal berakhir harus YYYY-MM-DD.',
            'tanggal_berakhir.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'jenis.required' => 'Jenis pengajuan wajib diisi.',
            'keterangan.required' => 'Keterangan wajib diisi.',
        ];
    }
}
