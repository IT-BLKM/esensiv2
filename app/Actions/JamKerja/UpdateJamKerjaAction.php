<?php

namespace App\Actions\JamKerja;

use App\Models\JamKerja;

class UpdateJamKerjaAction
{
    /**
     * @param  array{kode: string, nama: string, awal_masuk: string, jam_masuk: string, akhir_masuk: string, jam_pulang: string}  $data
     */
    public function __invoke(JamKerja $jamKerja, array $data): JamKerja
    {
        $jamKerja->update($data);

        return $jamKerja;
    }
}
