<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status kunci periode payroll TUKIN per bulan.
 *
 * Saat terkunci, presensi dan approval pengajuan pada bulan tersebut ditolak
 * agar angka TUKIN tidak berubah setelah difinalisasi. Koreksi TUKIN tetap
 * boleh dilakukan lewat jalur audit `tukin_koreksi`.
 */
class TukinPeriode extends Model
{
    protected $table = 'tukin_periode';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tahun',
        'bulan',
        'terkunci',
        'dikunci_oleh',
        'dikunci_pada',
        'dibuka_oleh',
        'dibuka_pada',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'terkunci' => 'boolean',
            'dikunci_pada' => 'datetime',
            'dibuka_pada' => 'datetime',
        ];
    }

    public function dikunciOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikunci_oleh');
    }

    public function dibukaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuka_oleh');
    }

    public static function terkunci(int $tahun, int $bulan): bool
    {
        return static::query()
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('terkunci', true)
            ->exists();
    }

    public static function adaTerkunciDalamRentang(CarbonInterface $mulai, CarbonInterface $selesai): bool
    {
        $bulan = $mulai->copy()->startOfMonth();
        $akhir = $selesai->copy()->startOfMonth();

        while ($bulan->lte($akhir)) {
            if (static::terkunci($bulan->year, $bulan->month)) {
                return true;
            }

            $bulan = $bulan->addMonth();
        }

        return false;
    }
}
