<?php

namespace App\Models;

use Database\Factories\KonfigurasiJamKerjaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KonfigurasiJamKerja extends Model
{
    /** @use HasFactory<KonfigurasiJamKerjaFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai spesifikasi Database Esensi.
     */
    protected $table = 'konfigurasi_jam_kerja';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pegawai',
        'id_jam_kerja',
        'hari',
    ];
}
