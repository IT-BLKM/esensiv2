<?php

namespace App\Models;

use Database\Factories\CutiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuti extends Model
{
    /** @use HasFactory<CutiFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai spesifikasi Database Esensi.
     */
    protected $table = 'cuti';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'maks_hari',
        'kuota',
        'maks_bawa',
    ];

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'id_cuti');
    }

    public function saldoCuti(): HasMany
    {
        return $this->hasMany(SaldoCuti::class, 'id_cuti');
    }
}
