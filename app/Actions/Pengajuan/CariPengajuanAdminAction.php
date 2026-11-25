<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class CariPengajuanAdminAction
{
    /**
     * Daftar pengajuan untuk panel admin dengan filter status, pegawai, dan
     * rentang tanggal. Filter tanggal memakai irisan rentang pengajuan
     * (bukan hanya tanggal_mulai) supaya pengajuan yang melewati batas
     * periode tetap muncul.
     *
     * @return Collection<int, Pengajuan>
     */
    public function __invoke(
        User $admin,
        ?string $status = null,
        ?int $idPegawai = null,
        ?string $tanggalDari = null,
        ?string $tanggalSampai = null,
    ): Collection {
        Gate::forUser($admin)->authorize('viewAny', Pengajuan::class);

        return Pengajuan::query()
            ->with(['pegawai', 'cuti'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($idPegawai !== null, fn ($query) => $query->where('id_pegawai', $idPegawai))
            ->when($tanggalDari !== null, fn ($query) => $query->where('tanggal_berakhir', '>=', $tanggalDari))
            ->when($tanggalSampai !== null, fn ($query) => $query->where('tanggal_mulai', '<=', $tanggalSampai))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
