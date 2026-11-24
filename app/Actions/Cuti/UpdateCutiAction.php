<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;

class UpdateCutiAction
{
    /**
     * @param  array{kode: string, nama: string, maks_hari?: int|null, kuota?: int|null, maks_bawa: int}  $data
     */
    public function __invoke(Cuti $cuti, array $data): Cuti
    {
        $cuti->update($data);

        return $cuti;
    }
}
