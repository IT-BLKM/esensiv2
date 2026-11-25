<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Laporan\LaporanBulananAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LaporanBulananRequest;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class LaporanController extends Controller
{
    /**
     * Laporan bulanan per pegawai (issue #42): filter pegawai/bulan/tahun,
     * daftar harian, dan rekap hadir/cuti/sakit/dl/terlambat.
     */
    public function __invoke(LaporanBulananRequest $request, LaporanBulananAction $laporan): View
    {
        /** @var User $user */
        $user = $request->user();
        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $sekarang = CarbonImmutable::now($zonaWaktu);
        $filter = $request->validated();

        $bulan = (int) ($filter['bulan'] ?? $sekarang->month);
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);
        $idPegawai = isset($filter['id_pegawai']) ? (int) $filter['id_pegawai'] : null;
        $pegawai = $idPegawai === null ? null : Pegawai::query()->find($idPegawai);

        if ($pegawai === null) {
            $this->authorize('viewAny', Pegawai::class);
        } else {
            $this->authorize('view', $pegawai);
        }

        $hasil = $pegawai === null ? null : $laporan($user, $pegawai, $bulan, $tahun);

        return view('admin.laporan', [
            'idPegawai' => $idPegawai,
            'pegawai' => $pegawai,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'labelPeriode' => $hasil['labelPeriode']
                ?? CarbonImmutable::create($tahun, $bulan, 1, 0, 0, 0, $zonaWaktu)
                    ->locale('id')
                    ->translatedFormat('F Y'),
            'baris' => $hasil['baris'] ?? [],
            'ringkasan' => $hasil['ringkasan'] ?? null,
            'daftarPegawai' => Pegawai::query()->orderBy('nama')->get(['id', 'nip', 'nama']),
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
