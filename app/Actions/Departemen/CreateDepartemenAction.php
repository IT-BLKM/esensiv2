<?php

namespace App\Actions\Departemen;

use App\Models\Departemen;

class CreateDepartemenAction
{
    /**
     * @param  array{kode: string, nama: string}  $data
     */
    public function __invoke(array $data): Departemen
    {
        return Departemen::create($data);
    }
}
