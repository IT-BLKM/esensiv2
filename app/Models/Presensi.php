<?php

namespace App\Models;

use Database\Factories\PresensiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    /** @use HasFactory<PresensiFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai spesifikasi Database Esensi.
     */
    protected $table = 'presensi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pegawai',
        'id_jam_kerja',
        'id_pengajuan',
        'tgl_presensi',
        'jam_masuk',
        'jam_keluar',
        'foto_masuk',
        'foto_keluar',
        'koordinat_masuk',
        'koordinat_keluar',
        'status',
    ];
}
