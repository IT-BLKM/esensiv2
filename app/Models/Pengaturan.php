<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan aplikasi yang dapat diubah admin tanpa menyentuh .env.
 *
 * Tabel key-value kecil; nilai yang belum pernah disimpan tetap memakai
 * fallback dari config agar instalasi lama tidak berubah perilakunya.
 */
class Pengaturan extends Model
{
    /**
     * Nama tabel eksplisit mengikuti konvensi model lain.
     */
    protected $table = 'pengaturan';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kunci',
        'nilai',
    ];

    public static function nilai(string $kunci, ?string $default = null): ?string
    {
        $nilai = static::query()->where('kunci', $kunci)->value('nilai');

        return $nilai === null ? $default : (string) $nilai;
    }

    public static function simpan(string $kunci, string $nilai): void
    {
        static::query()->updateOrCreate(
            ['kunci' => $kunci],
            ['nilai' => $nilai],
        );
    }
}
