<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHariLiburRequest extends FormRequest
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
            'tanggal' => ['required', 'date_format:Y-m-d', 'unique:hari_libur,tanggal'],
            'nama' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal libur wajib diisi.',
            'tanggal.date_format' => 'Format tanggal libur harus YYYY-MM-DD.',
            'tanggal.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
            'nama.required' => 'Nama hari libur wajib diisi.',
            'nama.string' => 'Nama hari libur harus berupa teks.',
            'nama.max' => 'Nama hari libur maksimal 255 karakter.',
        ];
    }
}
