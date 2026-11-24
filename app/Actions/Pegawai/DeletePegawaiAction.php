<?php

namespace App\Actions\Pegawai;

use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeletePegawaiAction
{
    /**
     * Hapus user beserta pegawai yang terikat (FK cascade) supaya tidak ada
     * akun yatim. File foto lama ikut dibersihkan.
     */
    public function __invoke(Pegawai $pegawai): bool
    {
        $foto = $pegawai->foto;
        $user = $pegawai->user;

        if ($user === null) {
            return false;
        }

        $deleted = DB::transaction(fn (): bool => (bool) $user->delete());

        if ($deleted && $foto !== null) {
            Storage::disk('public')->delete($foto);
        }

        return $deleted;
    }
}
