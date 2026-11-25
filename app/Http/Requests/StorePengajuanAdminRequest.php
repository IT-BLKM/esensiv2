<?php

namespace App\Http\Requests;

use App\Models\Pegawai;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validasi form admin "buat pengajuan untuk pegawai lain" (issue #104).
 *
 * Aturan bisnis diwarisi dari StorePengajuanRequest: kuota, bentrok presensi,
 * bentrok pengajuan approved, dan lampiran. Bedanya, subjek pegawai datang
 * dari `id_pegawai` yang dipilih admin, bukan dari akun yang login.
 */
class StorePengajuanAdminRequest extends StorePengajuanRequest
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
        return array_merge(parent::rules(), [
            'id_pegawai' => ['required', 'integer', 'exists:pegawai,id'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'id_pegawai.required' => 'Pegawai wajib dipilih.',
            'id_pegawai.integer' => 'Pegawai tidak valid.',
            'id_pegawai.exists' => 'Pegawai tidak ditemukan.',
        ]);
    }

    protected function pegawaiPengajuan(): ?Pegawai
    {
        $id = $this->input('id_pegawai');

        return $id === null ? null : Pegawai::query()->find((int) $id);
    }
}
