<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePresensiRequest extends FormRequest
{
    /**
     * Otorisasi akses presensi milik sendiri sudah dijaga route `role:pegawai`;
     * FormRequest hanya memvalidasi bentuk payload dari halaman presensi.
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
            'jenis' => ['required', Rule::in(['masuk', 'keluar'])],
            'foto' => ['required', 'string', 'max:8000000'],
            'koordinat' => ['required', 'string', 'regex:/^-?\d{1,3}(\.\d+)?,\s*-?\d{1,3}(\.\d+)?$/'],
            'akurasi' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * Regex di atas hanya membatasi jumlah digit; rentang lintang/bujur
     * diperiksa di sini supaya koordinat mustahil tidak lolos.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('koordinat')) {
                    return;
                }

                [$lintang, $bujur] = array_map(
                    static fn (string $bagian): float => (float) trim($bagian),
                    explode(',', (string) $this->input('koordinat')),
                );

                if ($lintang < -90 || $lintang > 90 || $bujur < -180 || $bujur > 180) {
                    $validator->errors()->add(
                        'koordinat',
                        'Koordinat harus berada di lintang -90..90 dan bujur -180..180.',
                    );
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
            'jenis.required' => 'Jenis presensi wajib diisi.',
            'jenis.in' => 'Jenis presensi harus masuk atau keluar.',
            'foto.required' => 'Foto presensi wajib diambil dari kamera.',
            'foto.string' => 'Foto presensi harus berupa data gambar.',
            'foto.max' => 'Ukuran foto terlalu besar.',
            'koordinat.required' => 'Lokasi wajib diambil sebelum absen.',
            'koordinat.regex' => 'Format koordinat tidak valid.',
            'akurasi.numeric' => 'Akurasi lokasi tidak valid.',
            'akurasi.min' => 'Akurasi lokasi tidak valid.',
            'akurasi.max' => 'Akurasi lokasi tidak valid.',
        ];
    }

    /**
     * Semua kegagalan validasi tetap berbentuk JSON terstruktur dengan `type`,
     * sama seperti kegagalan bisnis dari StorePresensiAction.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'type' => 'validasi',
            'message' => 'Data presensi tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
