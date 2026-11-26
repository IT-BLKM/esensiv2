<?php

namespace App\Actions\Laporan;

use App\Models\Pegawai;
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
            ->get(['id', 'nip', 'nama', 'id_dept']);

        $baris = [];

        foreach ($pegawai as $item) {
            $hasil = $bulan === null
                ? $this->hitungTukin->tahunan($item, $tahun)
                : $this->hitungTukin->bulanan($item, $tahun, $bulan);

            $baris[] = [
                'id' => (int) $item->getAttribute('id'),
                'nip' => (string) $item->getAttribute('nip'),
                'nama' => (string) $item->getAttribute('nama'),
                'departemen' => $item->departemen?->getAttribute('nama'),
                'hariPotongan' => count(array_filter(
                    $hasil['baris'],
                    fn (array $baris): bool => $baris['persenTotal'] > 0,
                )),
                'ringkasan' => $hasil['ringkasan'],
            ];
        }

        $totalPersenTl = round(array_sum(array_column(array_column($baris, 'ringkasan'), 'totalPersenTl')), 2);
        $totalPersenPsw = round(array_sum(array_column(array_column($baris, 'ringkasan'), 'totalPersenPsw')), 2);

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
                    fn (array $item): bool => $item['ringkasan']['totalPersen'] > 0,
                )),
                'totalPersenTl' => $totalPersenTl,
                'totalPersenPsw' => $totalPersenPsw,
                'totalPersen' => round($totalPersenTl + $totalPersenPsw, 2),
                'totalHariPotongan' => array_sum(array_column($baris, 'hariPotongan')),
            ],
        ];
    }
}
