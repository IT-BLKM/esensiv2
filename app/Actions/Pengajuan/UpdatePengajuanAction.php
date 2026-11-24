<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdatePengajuanAction
{
    /**
     * Ubah pengajuan hanya setelah policy memeriksa kepemilikan.
     * Field owner/status/approval sengaja tidak masuk $data.
     *
     * @param  array{tanggal_mulai: string, tanggal_berakhir: string, jenis: string, keterangan: string, id_cuti?: int|null, lampiran?: string|null}  $data
     */
    public function __invoke(User $user, Pengajuan $pengajuan, array $data): Pengajuan
    {
        Gate::forUser($user)->authorize('update', $pengajuan);

        $pengajuan->update($data);

        return $pengajuan;
    }
}
