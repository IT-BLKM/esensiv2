<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\HariLiburFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Hari libur nasional/cuti bersama yang tidak dihitung sebagai hari kerja.
 *
 * Tabel ini sengaja terpisah dari `konfigurasi_jam_kerja`: libur berlaku untuk
 * semua pegawai, sedangkan jadwal shift berlaku per pegawai.
 */
class HariLibur extends Model
{
    /** @use HasFactory<HariLiburFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit mengikuti konvensi model lain.
     */
    protected $table = 'hari_libur';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tanggal',
        'nama',
    ];

    public static function padaTanggal(CarbonInterface|string $tanggal): bool
    {
        $nilai = $tanggal instanceof CarbonInterface
            ? $tanggal->toDateString()
            : Carbon::parse($tanggal)->toDateString();

        return static::query()->whereDate('tanggal', $nilai)->exists();
    }

    /**
     * @return list<string>
     */
    public static function dalamRentang(CarbonInterface $mulai, CarbonInterface $selesai): array
    {
        return static::query()
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->orderBy('tanggal')
            ->pluck('tanggal')
            ->map(fn (mixed $tanggal): string => Carbon::parse((string) $tanggal)->toDateString())
            ->values()
            ->all();
    }
}
