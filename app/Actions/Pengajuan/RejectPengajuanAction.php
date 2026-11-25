<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Tolak pengajuan pending. Tidak membuat baris presensi dan tidak menyentuh
 * saldo; catatan alasan wajib diisi lewat TolakPengajuanRequest.
 */
class RejectPengajuanAction
{
    public function __invoke(User $admin, Pengajuan $pengajuan, string $catatan): Pengajuan
    {
        Gate::forUser($admin)->authorize('decide', $pengajuan);

        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));

        return DB::transaction(function () use ($admin, $pengajuan, $catatan, $sekarang): Pengajuan {
            $terkunci = Pengajuan::query()
                ->whereKey($pengajuan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($terkunci->status !== 'pending') {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Pengajuan ini sudah diputuskan sebelumnya.',
                ]);
            }

            $terkunci->update([
                'status' => 'rejected',
                'disetujui_oleh' => $admin->id,
                'disetujui_pada' => $sekarang->toDateTimeString(),
                'catatan' => trim($catatan),
            ]);

            return $terkunci->refresh();
        });
    }
}
