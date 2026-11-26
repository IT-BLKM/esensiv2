<?php

namespace App\Actions\Cuti;

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\SaldoCuti;
use Illuminate\Support\Facades\DB;

/**
 * Simpan tabungan cuti tahunan (Y-1 dan Y-2) dari input admin.
 *
 * Dipakai saat migrasi awal ketika saldo dari aplikasi lama tidak bisa
 * diimpor otomatis. Pemakaian tahun berjalan tetap dihitung dari baris
 * presensi/pengajuan approved, bukan dari angka yang diisi di sini.
 */
class SimpanSaldoAwalCutiAction
{
    /**
     * @param  array{saldo_tahun_lalu: int|string, saldo_dua_tahun_lalu: int|string}  $data
     */
    public function __invoke(Pegawai $pegawai, Cuti $cuti, int $tahunSekarang, array $data): void
    {
        $maksBawa = max(0, (int) $cuti->maks_bawa);

        DB::transaction(function () use ($pegawai, $cuti, $tahunSekarang, $data, $maksBawa): void {
            foreach ([
                $tahunSekarang - 1 => $data['saldo_tahun_lalu'],
                $tahunSekarang - 2 => $data['saldo_dua_tahun_lalu'],
            ] as $tahunSumber => $saldo) {
                SaldoCuti::updateOrCreate(
                    [
                        'id_pegawai' => $pegawai->id,
                        'id_cuti' => $cuti->id,
                        'tahun_sumber' => $tahunSumber,
                    ],
                    ['saldo' => max(0, min((int) $saldo, $maksBawa))],
                );
            }
        });
    }
}
