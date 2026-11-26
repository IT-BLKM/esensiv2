<?php

namespace App\Actions\Tukin;

use App\Actions\Laporan\HitungPotonganTunjanganKinerjaAction;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\TukinKoreksi;
use App\Models\TukinNominal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;

/**
 * Tren potongan TUKIN per bulan dalam satu tahun.
 *
 * Semua presensi tahun dimuat sekali, lalu dihitung per bulan per pegawai
 * memakai HitungPotonganTunjanganKinerjaAction::dariPresensi().
 */
class TrenTukinTahunanAction
{
    public function __construct(
        private readonly HitungPotonganTunjanganKinerjaAction $hitungTukin,
        private readonly LengkapiTukinAction $lengkapiTukin,
    ) {}

    /**
     * @return list<array{
     *     bulan: int,
     *     label: string,
     *     pegawaiKena: int,
     *     totalPersenTl: float,
     *     totalPersenPsw: float,
     *     totalPersenAkhir: float,
     *     potonganNominal: int|null
     * }>
     */
    public function __invoke(User $user, int $tahun): array
    {
        Gate::forUser($user)->authorize('viewAny', Pegawai::class);

        $pegawai = Pegawai::query()->get(['id', 'jabatan']);
        $nominal = TukinNominal::query()->pluck('nominal', 'jabatan');
        $presensi = Presensi::query()
            ->with(['jamKerja', 'pengajuan', 'penggantianKeterlambatan'])
            ->whereBetween('tgl_presensi', ["{$tahun}-01-01", "{$tahun}-12-31"])
            ->get()
            ->groupBy('id_pegawai');
        $koreksi = TukinKoreksi::query()
            ->where('tahun', $tahun)
            ->get()
            ->groupBy(fn (TukinKoreksi $item): string => $item->bulan.'|'.$item->id_pegawai);

        $hasil = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $awal = CarbonImmutable::create($tahun, $bulan, 1)->startOfMonth();
            $akhir = $awal->endOfMonth();
            $totalTl = 0.0;
            $totalPsw = 0.0;
            $totalAkhir = 0.0;
            $pegawaiKena = 0;
            $totalNominal = 0;
            $adaNominal = false;

            foreach ($pegawai as $item) {
                $presensiBulan = ($presensi->get($item->id) ?? collect())
                    ->filter(fn (Presensi $baris): bool => CarbonImmutable::parse((string) $baris->tgl_presensi)->month === $bulan)
                    ->values();
                $koreksiPegawai = $koreksi->get($bulan.'|'.$item->id) ?? collect();

                if ($presensiBulan->isEmpty() && $koreksiPegawai->isEmpty()) {
                    continue;
                }

                $hasilHitung = $this->hitungTukin->dariPresensi($presensiBulan, $awal, $akhir);
                $nominalPegawai = $nominal->get((string) $item->getAttribute('jabatan'));
                $lengkap = ($this->lengkapiTukin)(
                    $hasilHitung,
                    $nominalPegawai === null ? null : (int) $nominalPegawai,
                    $koreksiPegawai,
                );

                $totalTl += (float) $lengkap['ringkasan']['totalPersenTl'];
                $totalPsw += (float) $lengkap['ringkasan']['totalPersenPsw'];
                $totalAkhir += (float) $lengkap['ringkasan']['totalPersenAkhir'];

                if ($lengkap['ringkasan']['totalPersenAkhir'] > 0) {
                    $pegawaiKena++;
                }

                if ($lengkap['ringkasan']['potonganNominal'] !== null) {
                    $adaNominal = true;
                    $totalNominal += (int) $lengkap['ringkasan']['potonganNominal'];
                }
            }

            $hasil[] = [
                'bulan' => $bulan,
                'label' => $awal->locale('id')->translatedFormat('F'),
                'pegawaiKena' => $pegawaiKena,
                'totalPersenTl' => round($totalTl, 2),
                'totalPersenPsw' => round($totalPsw, 2),
                'totalPersenAkhir' => round($totalAkhir, 2),
                'potonganNominal' => $adaNominal ? $totalNominal : null,
            ];
        }

        return $hasil;
    }
}
