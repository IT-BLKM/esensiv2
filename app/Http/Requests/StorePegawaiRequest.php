<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePegawaiRequest extends FormRequest
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
        return [
            'nip' => ['required', 'string', 'size:18', 'regex:/^\d{18}$/', 'unique:pegawai,nip'],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'id_dept' => ['required', 'integer', 'exists:departemen,id'],
            'no_hp' => ['required', 'string', 'max:15', 'regex:/^0\d{8,14}$/'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nip.string' => 'NIP harus berupa teks.',
            'nip.size' => 'NIP harus terdiri dari 18 digit.',
            'nip.regex' => 'NIP hanya boleh berisi angka.',
            'nip.unique' => 'NIP sudah digunakan pegawai lain.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'jabatan.string' => 'Jabatan harus berupa teks.',
            'jabatan.max' => 'Jabatan maksimal 255 karakter.',
            'id_dept.required' => 'Departemen wajib dipilih.',
            'id_dept.integer' => 'Departemen tidak valid.',
            'id_dept.exists' => 'Departemen tidak ditemukan.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.string' => 'Nomor HP harus berupa teks.',
            'no_hp.max' => 'Nomor HP maksimal 15 karakter.',
            'no_hp.regex' => 'Nomor HP harus diawali 0 dan hanya berisi angka.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, JPEG, atau PNG.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }

    /**
     * Email user dibuat dari NIP, jadi NIP yang sudah dipakai akun lain harus
     * ditolak sebelum transaksi pegawai + user dijalankan.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('nip')) {
                    return;
                }

                $email = $this->string('nip')->toString().'@presensi.test';

                if (User::where('email', $email)->exists()) {
                    $validator->errors()->add('nip', 'NIP sudah memiliki akun pengguna.');
                }
            },
        ];
    }
}
