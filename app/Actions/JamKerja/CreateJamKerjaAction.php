<?php

namespace App\Actions\JamKerja;

use App\Models\JamKerja;

class CreateJamKerjaAction
{
    /**
     * @param  array{kode: string, nama: string, awal_masuk: string, jam_masuk: string, akhir_masuk: string, jam_pulang: string}  $data
     */
    public function __invoke(array $data): JamKerja
    {
        return JamKerja::create($data);
    }
}
