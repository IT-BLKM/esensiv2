<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTunjanganTingkatRequest extends FormRequest
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
            'tingkat' => ['required', 'array', 'min:1'],
            'tingkat.*.min' => ['required', 'integer', 'min:0', 'max:100000'],
            'tingkat.*.max' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'tingkat.*.persen' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tingkat = $this->input('tingkat', []);

                if (! is_array($tingkat)) {
                    return;
                }

                foreach ($tingkat as $id => $baris) {
                    if (! is_array($baris) || ! isset($baris['min']) || ! is_numeric($baris['min'])) {
                        continue;
                    }

                    $max = $baris['max'] ?? null;

                    if ($max !== null && $max !== '' && is_numeric($max) && (int) $max < (int) $baris['min']) {
                        $validator->errors()->add(
                            "tingkat.{$id}.max",
                            'Batas atas tidak boleh kurang dari batas bawah.',
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tingkat.required' => 'Data tingkat wajib diisi.',
            'tingkat.array' => 'Format data tingkat tidak valid.',
            'tingkat.*.min.required' => 'Batas bawah wajib diisi.',
            'tingkat.*.min.integer' => 'Batas bawah harus berupa angka bulat.',
            'tingkat.*.min.min' => 'Batas bawah tidak boleh negatif.',
            'tingkat.*.max.integer' => 'Batas atas harus berupa angka bulat.',
            'tingkat.*.max.min' => 'Batas atas tidak boleh negatif.',
            'tingkat.*.persen.required' => 'Persentase wajib diisi.',
            'tingkat.*.persen.numeric' => 'Persentase harus berupa angka.',
            'tingkat.*.persen.min' => 'Persentase tidak boleh negatif.',
            'tingkat.*.persen.max' => 'Persentase maksimal 100.',
        ];
    }
}
