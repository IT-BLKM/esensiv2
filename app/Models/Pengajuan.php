<?php

namespace App\Models;

use Database\Factories\PengajuanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    /** @use HasFactory<PengajuanFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai spesifikasi Database Esensi.
     */
    protected $table = 'pengajuan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pegawai',
        'id_cuti',
        'tanggal_mulai',
        'tanggal_berakhir',
        'jenis',
        'keterangan',
        'lampiran',
        'status',
        'disetujui_oleh',
        'disetujui_pada',
        'catatan',
    ];
}
