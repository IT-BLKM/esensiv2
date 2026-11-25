<?php

namespace App\Actions\Laporan;

use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Matriks rekap semua pegawai untuk satu bulan.
 *
 * Kode sel mengikuti ringkasan yang dipakai laporan lama:
 * H = hadir, DL = dinas luar, C = cuti, S = sakit, A = alpa.
 * Sel kosong berarti hari tanpa shift (akhir pekan/libur).
 */
class LaporanSemuaPegawaiAction
{
    /**
     * @return array{
     *     labelPeriode: string,
     *     hari: list<int>,
     *     baris: list<array{
     *         id: int,
     *         nip: string,
     *         nama: string,
     *         sel: array<int, string|null>,
     *         h: int,
     *         dl: int,
     *         c: int,
     *         s: int,
     *         a: int
     *     }>
     * }
     */
    public function __invoke(User $user, int $bulan, int $tahun): array
    {
        Gate::forUser($user)->authorize('viewAny', Pegawai::class);

        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $awal = CarbonImmutable::create($tahun, $bulan, 1, 0, 0, 0, $zonaWaktu)->startOfMonth();
        $akhir = $awal->endOfMonth();
        $jumlahHari = $awal->daysInMonth;

        $pegawai = Pegawai::query()
            ->with(['konfigurasiJamKerja:id_pegawai,hari,id_jam_kerja'])
            ->orderBy('nama')
            ->get(['id', 'nip', 'nama']);

        $presensi = Presensi::query()
            ->whereBetween('tgl_presensi', [$awal->toDateString(), $akhir->toDateString()])
            ->get(['id_pegawai', 'tgl_presensi', 'status'])
            ->groupBy('id_pegawai');

        $baris = [];

        foreach ($pegawai as $item) {
            $petaPresensi = ($presensi->get($item->id) ?? collect())
                ->keyBy(fn (Presensi $presensi): int => (int) Carbon::parse((string) $presensi->tgl_presensi)->day);

            $hariKerja = $item->konfigurasiJamKerja
                ->pluck('hari')
                ->all();

            $sel = [];
            $hitung = ['H' => 0, 'DL' => 0, 'C' => 0, 'S' => 0, 'A' => 0];

            for ($hari = 1; $hari <= $jumlahHari; $hari++) {
                $tanggal = $awal->setDay($hari);
                $presensiHari = $petaPresensi->get($hari);

                if ($presensiHari !== null) {
                    $kode = $this->kodeStatus($presensiHari->status);
                } elseif ($this->hariKerja($tanggal, $hariKerja)) {
                    $kode = 'A';
                } else {
                    $kode = null;
                }

                $sel[$hari] = $kode;

                if ($kode !== null) {
                    $hitung[$kode] = ($hitung[$kode] ?? 0) + 1;
                }
            }

            $baris[] = [
                'id' => $item->id,
                'nip' => $item->nip,
                'nama' => $item->nama,
                'sel' => $sel,
                'h' => $hitung['H'],
                'dl' => $hitung['DL'],
                'c' => $hitung['C'],
                's' => $hitung['S'],
                'a' => $hitung['A'],
            ];
        }

        return [
            'labelPeriode' => $awal->locale('id')->translatedFormat('F Y'),
            'hari' => range(1, $jumlahHari),
            'baris' => $baris,
        ];
    }

    /**
     * Hari kerja = hari yang punya shift. Tanpa konfigurasi, default
     * Senin–Jumat (sama dengan DaftarHariKerjaAction).
     *
     * @param  list<string>  $hariKerja
     */
    private function hariKerja(CarbonInterface $tanggal, array $hariKerja): bool
    {
        $namaHari = KonfigurasiJamKerja::HARI[($tanggal->dayOfWeek + 6) % 7];

        if ($hariKerja === []) {
            return in_array($namaHari, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'], true);
        }

        return in_array($namaHari, $hariKerja, true);
    }

    private function kodeStatus(string $status): string
    {
        return match ($status) {
            'hadir' => 'H',
            'dl' => 'DL',
            'cuti' => 'C',
            'sakit' => 'S',
            default => 'A',
        };
    }
}
