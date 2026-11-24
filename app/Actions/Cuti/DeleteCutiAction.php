<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;

class DeleteCutiAction
{
    /**
     * Hapus jenis cuti hanya bila belum dipakai pengajuan maupun saldo.
     *
     * FK `saldo_cuti.id_cuti` memakai cascadeOnDelete dan `pengajuan.id_cuti`
     * restrictOnDelete. Tanpa penjagaan ini, menghapus aturan bisa menghilangkan
     * tabungan cuti pegawai atau gagal di level database.
     */
    public function __invoke(Cuti $cuti): bool
    {
        if ($cuti->pengajuan()->exists() || $cuti->saldoCuti()->exists()) {
            return false;
        }

        return (bool) $cuti->delete();
    }
}
