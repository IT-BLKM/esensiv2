<?php

namespace App\Http\Requests;

use App\Models\Departemen;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartemenRequest extends FormRequest
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
        /** @var Departemen $departemen */
        $departemen = $this->route('departemen');

        return [
            'kode' => ['required', 'string', 'max:4', Rule::unique('departemen', 'kode')->ignore($departemen->id)],
            'nama' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode departemen wajib diisi.',
            'kode.string' => 'Kode departemen harus berupa teks.',
            'kode.max' => 'Kode departemen maksimal 4 karakter.',
            'kode.unique' => 'Kode departemen sudah digunakan.',
            'nama.required' => 'Nama departemen wajib diisi.',
            'nama.string' => 'Nama departemen harus berupa teks.',
            'nama.max' => 'Nama departemen maksimal 255 karakter.',
        ];
    }
}
