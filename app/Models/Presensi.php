<?php

namespace App\Models;

use Database\Factories\PresensiFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    /**
     * Batasi query ke data pegawai yang sedang login. Dipakai Action setelah
     * policy `viewAny` lolos; admin sengaja tidak memakai scope ini.
     */
    #[Scope]
    protected function milikSaya(Builder $query, User $user): Builder
    {
        return $query->where('id_pegawai', $user->pegawai?->id ?? 0);
    }
}
