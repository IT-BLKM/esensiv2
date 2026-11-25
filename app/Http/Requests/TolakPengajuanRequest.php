<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TolakPengajuanRequest extends FormRequest
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
            'catatan_tolak' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan_tolak.required' => 'Catatan alasan penolakan wajib diisi.',
            'catatan_tolak.string' => 'Catatan harus berupa teks.',
            'catatan_tolak.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
