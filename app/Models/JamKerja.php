<?php

namespace App\Models;

use Database\Factories\JamKerjaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JamKerja extends Model
{
    /** @use HasFactory<JamKerjaFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai spesifikasi Database Esensi.
     */
    protected $table = 'jam_kerja';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'awal_masuk',
        'jam_masuk',
        'akhir_masuk',
        'jam_pulang',
    ];

    /**
     * Jadwal pegawai yang memakai shift ini.
     */
    public function konfigurasiJamKerja(): HasMany
    {
        return $this->hasMany(KonfigurasiJamKerja::class, 'id_jam_kerja');
    }

    /**
     * Jejak presensi yang merekam shift ini.
     */
    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class, 'id_jam_kerja');
    }
}
