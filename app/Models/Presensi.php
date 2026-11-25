<?php

namespace App\Models;

use Database\Factories\PresensiFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Presensi extends Model
{
    /** @use HasFactory<PresensiFactory> */
    use HasFactory;

    /**
     * Label tampilan untuk enum `presensi.status`.
     *
     * @var array<string, string>
     */
    public const LABEL_STATUS = [
        'hadir' => 'Hadir',
        'cuti' => 'Cuti',
        'sakit' => 'Sakit',
        'dl' => 'Dinas Luar',
    ];

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
     * Shift yang berlaku saat baris presensi dicatat. Dipakai menghitung
     * keterlambatan dari `jam_kerja.jam_masuk` (issue #34), bukan angka tetap.
     */
    public function jamKerja(): BelongsTo
    {
        return $this->belongsTo(JamKerja::class, 'id_jam_kerja');
    }

    /**
     * Pengajuan yang melahirkan baris presensi ini (cuti/sakit/dl), bila ada.
     */
    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'id_pengajuan');
    }

    /**
     * Snapshot penggantian waktu keterlambatan (issue #109), bila sudah ada.
     */
    public function penggantianKeterlambatan(): HasOne
    {
        return $this->hasOne(PenggantianKeterlambatan::class, 'id_presensi');
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
