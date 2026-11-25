<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot penggantian waktu keterlambatan satu baris presensi.
 *
 * Dibuat otomatis saat absen pulang. Perhitungan tunjangan tetap bisa
 * menurunkan data dari presensi untuk baris lama yang belum punya snapshot.
 */
class PenggantianKeterlambatan extends Model
{
    protected $table = 'penggantian_keterlambatan';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'id_presensi',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'target_pulang',
        'batas_pulang',
        'menit_terlambat',
        'menit_diganti',
        'bisa_diganti',
        'terpenuhi',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'menit_terlambat' => 'integer',
            'menit_diganti' => 'integer',
            'bisa_diganti' => 'boolean',
            'terpenuhi' => 'boolean',
        ];
    }

    public function presensi(): BelongsTo
    {
        return $this->belongsTo(Presensi::class, 'id_presensi');
    }
}
