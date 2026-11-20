<?php

namespace App\Models;

use Database\Factories\JamKerjaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
