<?php

namespace App\Actions\Departemen;

use App\Models\Departemen;

class DeleteDepartemenAction
{
    /**
     * Hapus departemen hanya bila belum dipakai pegawai.
     *
     * FK `pegawai.id_dept` memakai cascadeOnDelete, jadi departemen yang masih
     * beranggotakan pegawai harus ditolak supaya data pegawai tidak ikut hilang.
     */
    public function __invoke(Departemen $departemen): bool
    {
        if ($departemen->pegawai()->exists()) {
            return false;
        }

        return (bool) $departemen->delete();
    }
}
