<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJamKerjaRequest extends FormRequest
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
     * Urutan jam diulang di sini karena bukan hanya constraint database
     * yang memastikan awal_masuk <= jam_masuk <= akhir_masuk <= jam_pulang.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'size:4', 'unique:jam_kerja,kode'],
            'nama' => ['required', 'string', 'max:25'],
            'awal_masuk' => ['required', 'date_format:H:i'],
            'jam_masuk' => ['required', 'date_format:H:i', 'after_or_equal:awal_masuk'],
            'akhir_masuk' => ['required', 'date_format:H:i', 'after_or_equal:jam_masuk'],
            'jam_pulang' => ['required', 'date_format:H:i', 'after_or_equal:akhir_masuk'],
        ];
    }
}
