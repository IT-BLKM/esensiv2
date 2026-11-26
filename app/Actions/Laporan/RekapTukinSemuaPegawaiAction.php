<?php

namespace App\Actions\Laporan;

use App\Actions\Tukin\AmbilKoreksiTukinAction;
use App\Actions\Tukin\LengkapiTukinAction;
use App\Models\Pegawai;
use App\Models\TukinNominal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;

/**
 * Rekap persentase potongan TUKIN seluruh pegawai untuk satu bulan/tahun.
 *
 * Angka per pegawai berasal dari HitungPotonganTunjanganKinerjaAction yang
 * sama dengan halaman rincian, sehingga rekap dan detail tidak pernah beda.
 */
class RekapTukinSemuaPegawaiAction
{
    public function __construct(
        private readonly HitungPotonganTunjanganKinerjaAction $hitungTukin,
        private readonly AmbilKoreksiTukinAction $ambilKoreksi,
        private readonly LengkapiTukinAction $lengkapiTukin,
    ) {}

    /**
     * @return array{
     *     labelPeriode: string,
     *     baris: list<array<string, mixed>>,
     *     ringkasan: array<string, mixed>
     * }
     */
    public function __invoke(User $user, int $tahun, ?int $bulan): array
    {
        Gate::forUser($user)->authorize('viewAny', Pegawai::class);

        $pegawai = Pegawai::query()
            ->with('departemen:id,nama')
            ->orderBy('nama')
            ->get(['id', 'nip', 'nama', 'jabatan', 'id_dept']);

        $nominal = TukinNominal::query()->pluck('nominal', 'jabatan');
        $koreksi = $this->ambilKoreksi->semua($tahun, $bulan);
        $baris = [];

        foreach ($pegawai as $item) {
            $hasil = $bulan === null
                ? $this->hitungTukin->tahunan($item, $tahun)
                : $this->hitungTukin->bulanan($item, $tahun, $bulan);
            $nominalPegawai = $nominal->get((string) $item->getAttribute('jabatan'));
            $hasil = ($this->lengkapiTukin)(
                $hasil,
                $nominalPegawai === null ? null : (int) $nominalPegawai,
                $koreksi->get($item->id) ?? collect(),
            );

            $baris[] = [
                'id' => (int) $item->getAttribute('id'),
                'nip' => (string) $item->getAttribute('nip'),
                'nama' => (string) $item->getAttribute('nama'),
                'jabatan' => (string) $item->getAttribute('jabatan'),
                'departemen' => $item->departemen?->getAttribute('nama'),
                'hariPotongan' => count(array_filter(
                    $hasil['baris'],
                    fn (array $baris): bool => $baris['persenTotal'] > 0,
                )),
                'ringkasan' => $hasil['ringkasan'],
            ];
        }

        $ringkasanPegawai = array_column($baris, 'ringkasan');
        $totalPersenTl = round(array_sum(array_column($ringkasanPegawai, 'totalPersenTl')), 2);
        $totalPersenPsw = round(array_sum(array_column($ringkasanPegawai, 'totalPersenPsw')), 2);
        $totalPersenAkhir = round(array_sum(array_column($ringkasanPegawai, 'totalPersenAkhir')), 2);
        $nominalPotongan = array_filter(
            array_column($ringkasanPegawai, 'potonganNominal'),
            fn (mixed $nilai): bool => $nilai !== null,
        );

        return [
            'labelPeriode' => $bulan === null
                ? (string) $tahun
                : CarbonImmutable::create($tahun, $bulan, 1)
                    ->locale('id')
                    ->translatedFormat('F Y'),
            'baris' => $baris,
            'ringkasan' => [
                'totalPegawai' => count($baris),
                'pegawaiKenaPotongan' => count(array_filter(
                    $baris,
                    fn (array $item): bool => $item['ringkasan']['totalPersenAkhir'] > 0,
                )),
                'totalPersenTl' => $totalPersenTl,
                'totalPersenPsw' => $totalPersenPsw,
                'totalPersen' => round($totalPersenTl + $totalPersenPsw, 2),
                'totalPersenAkhir' => $totalPersenAkhir,
                'totalKoreksi' => round(array_sum(array_column($ringkasanPegawai, 'koreksiPersen')), 2),
                'totalNominalPotongan' => $nominalPotongan === [] ? null : (int) array_sum($nominalPotongan),
                'totalHariPotongan' => array_sum(array_column($baris, 'hariPotongan')),
            ],
        ];
    }
}
