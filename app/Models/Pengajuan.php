<?php

namespace App\Models;

use Database\Factories\PengajuanFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengajuan extends Model
{
    /** @use HasFactory<PengajuanFactory> */
    use HasFactory;

    public const MODE_DL_FULL = 'full';

    public const MODE_DL_SETENGAH = 'setengah';

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
        'mode_dl',
        'keterangan',
        'lampiran',
        'status',
        'disetujui_oleh',
        'disetujui_pada',
        'catatan',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    /**
     * Jenis cuti untuk pengajuan `jenis=cuti` (null untuk sakit/dl).
     */
    public function cuti(): BelongsTo
    {
        return $this->belongsTo(Cuti::class, 'id_cuti');
    }

    /**
     * Admin yang menyetujui/menolak pengajuan.
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
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
