<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tingkat potongan TUKIN yang dapat diubah admin.
 *
 * Bila tabel kosong, aplikasi memakai fallback config/tunjangan.php agar
 * instalasi lama tetap berjalan.
 */
class TunjanganTingkat extends Model
{
    public const KATEGORI_KETERLAMBATAN = 'keterlambatan';

    public const KATEGORI_PULANG_CEPAT = 'pulang_cepat';

    protected $table = 'tunjangan_tingkat';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kategori',
        'level',
        'min',
        'max',
        'persen',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min' => 'integer',
            'max' => 'integer',
            'persen' => 'float',
        ];
    }
}
