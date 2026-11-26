<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Koreksi persentase TUKIN manual dengan jejak audit.
 *
 * Nilai bisa negatif untuk mengurangi potongan. Untuk membatalkan koreksi,
 * tambahkan koreksi berlawanan; baris lama tidak dihapus.
 */
class TukinKoreksi extends Model
{
    protected $table = 'tukin_koreksi';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_pegawai',
        'tahun',
        'bulan',
        'persen',
        'alasan',
        'dibuat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'persen' => 'float',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
