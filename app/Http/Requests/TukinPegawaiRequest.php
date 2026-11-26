<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TukinPegawaiRequest extends FormRequest
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
            'bulan.integer' => 'Bulan harus berupa angka.',
            'bulan.between' => 'Bulan tidak valid.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.between' => 'Tahun tidak valid.',
        ];
    }
}
