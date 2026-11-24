<?php

namespace App\Models;

use Database\Factories\PegawaiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pegawai extends Model
{
    /** @use HasFactory<PegawaiFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai spesifikasi Database Esensi.
     */
    protected $table = 'pegawai';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'id_dept',
        'nip',
        'nama',
        'jabatan',
        'foto',
        'no_hp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'id_pegawai');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class, 'id_pegawai');
    }

    public function konfigurasiJamKerja(): HasMany
    {
        return $this->hasMany(KonfigurasiJamKerja::class, 'id_pegawai');
    }
}
