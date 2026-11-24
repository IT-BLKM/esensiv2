<?php

namespace App\Models;

use Database\Factories\KonfigurasiJamKerjaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KonfigurasiJamKerja extends Model
{
    /** @use HasFactory<KonfigurasiJamKerjaFactory> */
    use HasFactory;

    /** Urutan hari yang dipakai form dan ringkasan jadwal. */
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

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

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    public function jamKerja(): BelongsTo
    {
        return $this->belongsTo(JamKerja::class, 'id_jam_kerja');
    }
}
