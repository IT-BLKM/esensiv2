<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLokasiKantorRequest extends FormRequest
{
    /**
     * Route sudah dilindungi middleware role:admin dan policy konfigurasi.
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
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Lintang kantor wajib diisi.',
            'latitude.numeric' => 'Lintang kantor harus berupa angka.',
            'latitude.between' => 'Lintang kantor harus berada di antara -90 dan 90.',
            'longitude.required' => 'Bujur kantor wajib diisi.',
            'longitude.numeric' => 'Bujur kantor harus berupa angka.',
            'longitude.between' => 'Bujur kantor harus berada di antara -180 dan 180.',
            'radius.required' => 'Radius absen wajib diisi.',
            'radius.integer' => 'Radius absen harus berupa angka bulat.',
            'radius.min' => 'Radius absen minimal 1 meter.',
            'radius.max' => 'Radius absen maksimal 100.000 meter.',
        ];
    }
}
