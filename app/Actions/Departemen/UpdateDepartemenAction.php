<?php

namespace App\Actions\Departemen;

use App\Models\Departemen;

class UpdateDepartemenAction
{
    /**
     * @param  array{kode: string, nama: string}  $data
     */
    public function __invoke(Departemen $departemen, array $data): Departemen
    {
        $departemen->update($data);

        return $departemen;
    }
}
