<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\TukinPeriode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Batalkan persetujuan pengajuan (issue #40).
 *
 * Satu transaksi: kunci baris, hapus semua presensi yang lahir dari
 * pengajuan ini (`id_pengajuan`), lalu kembalikan status dan jejak approval
 * ke kondisi sebelum approve. `saldo_cuti` tidak disentuh karena konsumsi
 * diturunkan dari baris presensi + saldo.
 */
class CancelApprovalPengajuanAction
{
    /**
     * @return array{pengajuan: Pengajuan, jumlahPresensiDihapus: int}
     */
    public function __invoke(User $admin, Pengajuan $pengajuan): array
    {
        Gate::forUser($admin)->authorize('decide', $pengajuan);

        return DB::transaction(function () use ($pengajuan): array {
            $terkunci = Pengajuan::query()
                ->whereKey($pengajuan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($terkunci->status !== 'approved') {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Hanya pengajuan yang sudah disetujui yang dapat dibatalkan.',
                ]);
            }

            $mulai = CarbonImmutable::parse((string) $terkunci->tanggal_mulai)->startOfDay();
            $selesai = CarbonImmutable::parse((string) $terkunci->tanggal_berakhir)->startOfDay();

            if (TukinPeriode::adaTerkunciDalamRentang($mulai, $selesai)) {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Tidak bisa dibatalkan: periode TUKIN pada rentang ini sudah dikunci.',
                ]);
            }

            $jumlahPresensi = Presensi::query()
                ->where('id_pengajuan', $terkunci->id)
                ->delete();

            $terkunci->update([
                'status' => 'pending',
                'disetujui_oleh' => null,
                'disetujui_pada' => null,
                'catatan' => null,
            ]);

            return [
                'pengajuan' => $terkunci->refresh(),
                'jumlahPresensiDihapus' => $jumlahPresensi,
            ];
        });
    }
}
