<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Presensi\CariHistoriPresensiAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\HistoriPresensiRequest;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HistoriPresensiController extends Controller
{
    /**
     * Histori presensi pegawai (issue #33): filter bulan/tahun, daftar
     * presensi, status, dan keterlambatan per hari. Perhitungan keterlambatan
     * memakai HitungMenitTerlambatAction yang sama dengan dashboard (#34).
     */
    public function __invoke(HistoriPresensiRequest $request, CariHistoriPresensiAction $cari): View
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;
        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $sekarang = CarbonImmutable::now($zonaWaktu);

        $filter = $request->validated();
        $bulan = (int) ($filter['bulan'] ?? $sekarang->month);
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);

        return view('pegawai.histori', [
            'adaPegawai' => $pegawai !== null,
            'histori' => $pegawai === null ? [] : $cari($pegawai, $bulan, $tahun),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'pilihanBulan' => $this->pilihanBulan(),
            'pilihanTahun' => $this->pilihanTahun($pegawai, $tahun, $sekarang->year),
            'labelPeriode' => CarbonImmutable::create($tahun, $bulan, 1, 0, 0, 0, $zonaWaktu)
                ->locale('id')
                ->translatedFormat('F Y'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function pilihanBulan(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $bulan): array => [
                $bulan => CarbonImmutable::create(2000, $bulan, 1)
                    ->locale('id')
                    ->translatedFormat('F'),
            ])
            ->all();
    }

    /**
     * Tahun yang bisa dipilih: dari tahun presensi paling awal milik pegawai
     * sampai tahun berjalan, ditambah tahun yang sedang difilter agar pilihan
     * tetap tampil walau tidak ada data.
     *
     * @return list<int>
     */
    private function pilihanTahun(?Pegawai $pegawai, int $tahunDipilih, int $tahunSekarang): array
    {
        $tahunAwal = $tahunSekarang;

        if ($pegawai !== null) {
            $terawal = Presensi::query()
                ->where('id_pegawai', $pegawai->id)
                ->min('tgl_presensi');

            if (is_string($terawal) && $terawal !== '') {
                $tahunAwal = min($tahunAwal, (int) Carbon::parse($terawal)->year);
            }
        }

        $pilihan = range($tahunSekarang, $tahunAwal);

        if (! in_array($tahunDipilih, $pilihan, true)) {
            $pilihan[] = $tahunDipilih;
            rsort($pilihan);
        }

        return array_values($pilihan);
    }
}
