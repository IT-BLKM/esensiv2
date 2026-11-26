<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTukinNominalRequest extends FormRequest
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
            'jabatan' => ['required', 'string', 'max:255', 'unique:tukin_nominal,jabatan'],
            'nominal' => ['required', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jabatan.required' => 'Jabatan wajib diisi.',
            'jabatan.unique' => 'Nominal untuk jabatan ini sudah ada.',
            'nominal.required' => 'Nominal wajib diisi.',
            'nominal.integer' => 'Nominal harus berupa angka bulat.',
            'nominal.min' => 'Nominal tidak boleh negatif.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ];
    }
}
