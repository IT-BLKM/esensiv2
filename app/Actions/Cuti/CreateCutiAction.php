<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;

class CreateCutiAction
{
    /**
     * @param  array{kode: string, nama: string, maks_hari?: int|null, kuota?: int|null, maks_bawa: int}  $data
     */
    public function __invoke(array $data): Cuti
    {
        return Cuti::create($data);
    }
}
