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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode jam kerja wajib diisi.',
            'kode.string' => 'Kode jam kerja harus berupa teks.',
            'kode.size' => 'Kode jam kerja harus tepat 4 karakter.',
            'kode.unique' => 'Kode jam kerja sudah digunakan.',
            'nama.required' => 'Nama jam kerja wajib diisi.',
            'nama.string' => 'Nama jam kerja harus berupa teks.',
            'nama.max' => 'Nama jam kerja maksimal 25 karakter.',
            'awal_masuk.required' => 'Jam awal masuk wajib diisi.',
            'awal_masuk.date_format' => 'Jam awal masuk harus berformat HH:MM.',
            'jam_masuk.required' => 'Jam masuk wajib diisi.',
            'jam_masuk.date_format' => 'Jam masuk harus berformat HH:MM.',
            'jam_masuk.after_or_equal' => 'Jam masuk tidak boleh lebih awal dari jam awal masuk.',
            'akhir_masuk.required' => 'Jam akhir masuk wajib diisi.',
            'akhir_masuk.date_format' => 'Jam akhir masuk harus berformat HH:MM.',
            'akhir_masuk.after_or_equal' => 'Jam akhir masuk tidak boleh lebih awal dari jam masuk.',
            'jam_pulang.required' => 'Jam pulang wajib diisi.',
            'jam_pulang.date_format' => 'Jam pulang harus berformat HH:MM.',
            'jam_pulang.after_or_equal' => 'Jam pulang tidak boleh lebih awal dari jam akhir masuk.',
        ];
    }
}
