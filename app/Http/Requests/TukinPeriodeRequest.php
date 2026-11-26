<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TukinPeriodeRequest extends FormRequest
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
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun periode wajib diisi.',
            'tahun.between' => 'Tahun periode tidak valid.',
            'bulan.required' => 'Bulan periode wajib diisi.',
            'bulan.between' => 'Bulan periode tidak valid.',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
