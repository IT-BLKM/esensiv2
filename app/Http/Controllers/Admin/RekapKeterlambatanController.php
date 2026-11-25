<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Laporan\RekapKehadiranAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RekapKeterlambatanRequest;
use App\Models\Pegawai;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class RekapKeterlambatanController extends Controller
{
    /**
     * Rekap keterlambatan bulanan atau akumulasi tahunan semua pegawai
     * (issue #44). Semua angka berasal dari RekapKehadiranAction.
     */
    public function __invoke(
        RekapKeterlambatanRequest $request,
        RekapKehadiranAction $rekap,
    ): View {
        $this->authorize('viewAny', Pegawai::class);

        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $sekarang = CarbonImmutable::now($zonaWaktu);
        $filter = $request->validated();
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);
        $bulan = $request->query->has('bulan')
            ? (isset($filter['bulan']) ? (int) $filter['bulan'] : null)
            : $sekarang->month;

        $hasil = $bulan === null
            ? $rekap->semuaTahunan($tahun)
            : $rekap->semuaBulanan($tahun, $bulan);

        return view('admin.rekap-keterlambatan', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'labelPeriode' => $hasil['labelPeriode'],
            'baris' => $hasil['baris'],
            'ringkasan' => $hasil['ringkasan'],
            'pilihanBulan' => $this->pilihanBulan(),
            'pilihanTahun' => $this->pilihanTahun($sekarang->year, $tahun),
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
     * @return list<int>
     */
    private function pilihanTahun(int $tahunSekarang, int $tahunDipilih): array
    {
        $terawal = Presensi::query()->min('tgl_presensi');
        $tahunAwal = is_string($terawal) && $terawal !== ''
            ? min($tahunSekarang, (int) Carbon::parse($terawal)->year)
            : $tahunSekarang;

        $pilihan = range($tahunSekarang, $tahunAwal);

        if (! in_array($tahunDipilih, $pilihan, true)) {
            $pilihan[] = $tahunDipilih;
            rsort($pilihan);
        }

        return array_values($pilihan);
    }
}
