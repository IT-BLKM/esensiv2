<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nominal dasar tunjangan kinerja per jabatan. Opsional: bila suatu jabatan
 * tidak punya baris di sini, laporan TUKIN tetap menampilkan persentase saja.
 */
class TukinNominal extends Model
{
    protected $table = 'tukin_nominal';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'jabatan',
        'nominal',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
        ];
    }
}
