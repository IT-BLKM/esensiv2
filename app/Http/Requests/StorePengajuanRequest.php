<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengajuanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Aturan id_cuti mencerminkan CHECK constraint di database:
     * jenis='cuti' ⇔ id_cuti terisi.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_pegawai' => ['required', 'integer'],
            'id_cuti' => [
                'nullable',
                'integer',
                'required_if:jenis,cuti',
                'prohibited_if:jenis,sakit,dl',
            ],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'jenis' => ['required', Rule::in(['cuti', 'sakit', 'dl'])],
            'keterangan' => ['required', 'string', 'max:255'],
            'lampiran' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'disetujui_oleh' => ['nullable', 'integer'],
            'disetujui_pada' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
