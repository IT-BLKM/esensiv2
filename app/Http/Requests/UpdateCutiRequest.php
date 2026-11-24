<?php

namespace App\Http\Requests;

use App\Models\Cuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCutiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        /** @var Cuti $cuti */
        $cuti = $this->route('cuti');

        return [
            'kode' => ['required', 'string', 'max:4', Rule::unique('cuti', 'kode')->ignore($cuti->id)],
            'nama' => ['required', 'string', 'max:25'],
            'maks_hari' => ['nullable', 'integer', 'min:0'],
            'kuota' => ['nullable', 'integer', 'min:0'],
            'maks_bawa' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode cuti wajib diisi.',
            'kode.string' => 'Kode cuti harus berupa teks.',
            'kode.max' => 'Kode cuti maksimal 4 karakter.',
            'kode.unique' => 'Kode cuti sudah digunakan.',
            'nama.required' => 'Nama cuti wajib diisi.',
            'nama.string' => 'Nama cuti harus berupa teks.',
            'nama.max' => 'Nama cuti maksimal 25 karakter.',
            'maks_hari.integer' => 'Maksimal hari harus berupa angka.',
            'maks_hari.min' => 'Maksimal hari tidak boleh negatif.',
            'kuota.integer' => 'Kuota harus berupa angka.',
            'kuota.min' => 'Kuota tidak boleh negatif.',
            'maks_bawa.required' => 'Maksimal bawa wajib diisi.',
            'maks_bawa.integer' => 'Maksimal bawa harus berupa angka.',
            'maks_bawa.min' => 'Maksimal bawa tidak boleh negatif.',
        ];
    }
}
