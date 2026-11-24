<?php

namespace App\Actions\JamKerja;

use App\Models\JamKerja;

class DeleteJamKerjaAction
{
    /**
     * Hapus jam kerja hanya bila belum dipakai konfigurasi atau presensi.
     *
     * FK `konfigurasi_jam_kerja.id_jam_kerja` memakai cascadeOnDelete dan FK
     * `presensi.id_jam_kerja` memakai nullOnDelete. Tanpa penjagaan ini, menghapus
     * satu shift bisa menghilangkan jadwal pegawai atau jejak absen historis.
     */
    public function __invoke(JamKerja $jamKerja): bool
    {
        if ($jamKerja->konfigurasiJamKerja()->exists() || $jamKerja->presensi()->exists()) {
            return false;
        }

        return (bool) $jamKerja->delete();
    }
}
