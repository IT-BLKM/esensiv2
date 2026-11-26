<?php

namespace App\Actions\Laporan;

use App\Models\HariLibur;
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
 * H = hadir, H! = hadir terlambat, DL = dinas luar, C = cuti, S = sakit,
 * A = alpa. Sel kosong berarti hari tanpa shift (akhir pekan/libur).
 *
 * Kolom TL dan PSW sengaja dihitung dari presensi mentah, bukan dari potongan
 * tunjangan: keterlambatan yang sudah diganti tetap tercatat di laporan,
 * sedangkan HitungPotonganTunjanganKinerjaAction yang menghapus potongannya.
 */
class LaporanSemuaPegawaiAction
{
    public function __construct(
        private readonly KlasifikasiHariPresensiAction $klasifikasi,
    ) {}

    /**
     * @return array{
     *     labelPeriode: string,
     *     hari: list<int>,
     *     akhirPekan: list<int>,
     *     baris: list<array{
     *         id: int,
     *         nip: string,
     *         nama: string,
     *         sel: array<int, string|null>,
     *         h: int,
     *         dl: int,
     *         c: int,
     *         s: int,
     *         a: int,
     *         tl: int,
     *         psw: int
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
        $hariLibur = array_fill_keys(HariLibur::dalamRentang($awal, $akhir), true);

        $pegawai = Pegawai::query()
            ->with(['konfigurasiJamKerja:id_pegawai,hari,id_jam_kerja'])
            ->orderBy('nama')
            ->get(['id', 'nip', 'nama']);

        $presensi = Presensi::query()
            ->with(['jamKerja', 'pengajuan', 'penggantianKeterlambatan'])
            ->whereBetween('tgl_presensi', [$awal->toDateString(), $akhir->toDateString()])
            ->get(['id_pegawai', 'id_jam_kerja', 'id_pengajuan', 'tgl_presensi', 'jam_masuk', 'jam_keluar', 'status'])
            ->groupBy('id_pegawai');

        $baris = [];

        foreach ($pegawai as $item) {
            $petaPresensi = ($presensi->get($item->id) ?? collect())
                ->keyBy(fn (Presensi $presensi): int => (int) Carbon::parse((string) $presensi->tgl_presensi)->day);

            $hariKerja = $item->konfigurasiJamKerja
                ->pluck('hari')
                ->all();

            $sel = [];
            $hitung = ['H' => 0, 'DL' => 0, 'C' => 0, 'S' => 0, 'A' => 0, 'TL' => 0, 'PSW' => 0];

            for ($hari = 1; $hari <= $jumlahHari; $hari++) {
                $tanggal = $awal->setDay($hari);
                $presensiHari = $petaPresensi->get($hari);

                $kunciStatus = null;

                if ($presensiHari !== null) {
                    $klasifikasi = ($this->klasifikasi)($presensiHari);
                    $kode = implode("\n", $klasifikasi['baris']);
                    $kunciStatus = $klasifikasi['kodeStatus'];

                    if ($klasifikasi['terlambat']) {
                        $hitung['TL']++;
                    }

                    if ($klasifikasi['pulangCepat']) {
                        $hitung['PSW']++;
                    }
                } elseif (! isset($hariLibur[$tanggal->toDateString()]) && $this->hariKerja($tanggal, $hariKerja)) {
                    $kode = 'A';
                    $kunciStatus = 'A';
                } else {
                    $kode = null;
                }

                $sel[$hari] = $kode;

                if ($kunciStatus !== null) {
                    $hitung[$kunciStatus] = ($hitung[$kunciStatus] ?? 0) + 1;
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
                'tl' => $hitung['TL'],
                'psw' => $hitung['PSW'],
            ];
        }

        return [
            'labelPeriode' => $awal->locale('id')->translatedFormat('F Y'),
            'hari' => range(1, $jumlahHari),
            'akhirPekan' => array_values(array_filter(
                range(1, $jumlahHari),
                fn (int $hari): bool => $awal->setDay($hari)->isWeekend(),
            )),
            'hariLibur' => array_values(array_map(
                fn (string $tanggal): int => (int) Carbon::parse($tanggal)->day,
                array_keys($hariLibur),
            )),
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
}
