<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Laporan\RekapTukinSemuaPegawaiAction;
use App\Actions\Tukin\AmbilDetailTukinAction;
use App\Actions\Tukin\TrenTukinTahunanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TukinRequest;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\TukinPeriode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class TukinController extends Controller
{
    public function __invoke(
        TukinRequest $request,
        RekapTukinSemuaPegawaiAction $rekap,
        AmbilDetailTukinAction $ambilDetail,
        TrenTukinTahunanAction $trenTukin,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewAny', Pegawai::class);

        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $sekarang = CarbonImmutable::now($zonaWaktu);
        $filter = $request->validated();
        $tahun = (int) ($filter['tahun'] ?? $sekarang->year);
        $bulan = $request->query->has('bulan')
            ? (isset($filter['bulan']) ? (int) $filter['bulan'] : null)
            : $sekarang->month;
        $idPegawai = isset($filter['id_pegawai']) ? (int) $filter['id_pegawai'] : null;
        $pegawai = $idPegawai === null ? null : Pegawai::query()->find($idPegawai);

        if ($pegawai !== null) {
            $this->authorize('view', $pegawai);
        }

        $hasil = $rekap($user, $tahun, $bulan);
        $perDepartemen = collect($hasil['baris'])
            ->groupBy(fn (array $item): string => $item['departemen'] ?? 'Tanpa departemen')
            ->map(function ($rows, string $nama): array {
                $ringkasan = $rows->pluck('ringkasan');
                $nominal = $ringkasan->pluck('potonganNominal')->filter(fn (mixed $nilai): bool => $nilai !== null);

                return [
                    'departemen' => $nama,
                    'jumlahPegawai' => $rows->count(),
                    'pegawaiKena' => $rows->filter(fn (array $item): bool => $item['ringkasan']['totalPersenAkhir'] > 0)->count(),
                    'totalPersenTl' => round($ringkasan->sum('totalPersenTl'), 2),
                    'totalPersenPsw' => round($ringkasan->sum('totalPersenPsw'), 2),
                    'totalPersenAkhir' => round($ringkasan->sum('totalPersenAkhir'), 2),
                    'potonganNominal' => $nominal->isEmpty() ? null : (int) $nominal->sum(),
                ];
            })
            ->sortByDesc('totalPersenAkhir')
            ->values()
            ->all();
        $tren = $trenTukin($user, $tahun);
        $detail = null;

        if ($pegawai !== null) {
            $detail = $ambilDetail($pegawai, $tahun, $bulan);
        }

        $periode = $bulan === null
            ? null
            : TukinPeriode::query()
                ->with('dikunciOleh:id,nama')
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->first();

        return view('admin.tukin', [
            'baris' => $hasil['baris'],
            'ringkasan' => $hasil['ringkasan'],
            'perDepartemen' => $perDepartemen,
            'tren' => $tren,
            'labelPeriode' => $hasil['labelPeriode'],
            'detail' => $detail,
            'pegawai' => $pegawai,
            'idPegawai' => $idPegawai,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode' => $periode,
            'terkunci' => $periode !== null && $periode->terkunci,
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

        return $pilihan;
    }
}
