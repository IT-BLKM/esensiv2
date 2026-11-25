<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Laporan\LaporanBulananAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanBulananRequest;
use App\Models\Pegawai;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class LaporanController extends Controller
{
    /**
     * Data laporan bulanan satu pegawai (JSON).
     *
     * Policy `view` menolak pegawai yang membuka laporan pegawai lain;
     * admin boleh membuka semua. Filter bulan/tahun opsional, default bulan
     * berjalan pada zona waktu kantor.
     */
    public function show(
        LaporanBulananRequest $request,
        Pegawai $pegawai,
        LaporanBulananAction $laporan,
    ): JsonResponse {
        $this->authorize('view', $pegawai);

        /** @var User $user */
        $user = $request->user();
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
        $filter = $request->validated();
        $bulan = (int) ($filter['bulan'] ?? $sekarang->month);
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);

        $hasil = $laporan($user, $pegawai, $bulan, $tahun);

        return response()->json([
            'id_pegawai' => $pegawai->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'label_periode' => $hasil['labelPeriode'],
            'jumlah_presensi' => count($hasil['baris']),
            'ringkasan' => $hasil['ringkasan'],
            'baris' => $hasil['baris'],
        ]);
    }
}
