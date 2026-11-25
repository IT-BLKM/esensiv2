<?php

namespace App\Actions\Laporan;

use App\Actions\Presensi\HitungMenitTerlambatAction;
use App\Actions\Presensi\HitungPenggantianKeterlambatanAction;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Hitung potongan tunjangan kinerja dari keterlambatan (TL) dan pulang
 * sebelum waktu (PSW), mengikuti Lampiran I & II Permenkes RI No. 41/2022.
 *
 * Satu baris presensi menghasilkan paling banyak satu tingkat TL dan satu
 * tingkat PSW. Persentase dijumlahkan per periode; nominal rupiah belum
 * dihitung karena master tunjangan belum ada.
 *
 * Frasa "bila tidak mengganti waktu keterlambatan" dijalankan lewat
 * HitungPenggantianKeterlambatanAction: TL 0 bila pegawai memenuhi jendela
 * pulang pengganti pada hari yang sama. PSW dihitung otomatis dari
 * `jam_keluar` terhadap `jam_pulang` shift.
 */
class HitungPotonganTunjanganKinerjaAction
{
    private const LEVEL_TANPA_MASUK = 'TL3';

    private const LEVEL_TANPA_PULANG = 'PSW4';

    public function __construct(
        private readonly HitungMenitTerlambatAction $hitungMenitTerlambat,
        private readonly HitungPenggantianKeterlambatanAction $hitungPenggantian,
    ) {}

    /**
     * @return array{
     *     mulai: string,
     *     selesai: string,
     *     baris: list<array{
     *         tanggal: string,
     *         menitTerlambat: int|null,
     *         levelTl: string|null,
     *         persenTl: float,
     *         menitDiganti: int,
     *         bisaDiganti: bool,
     *         penggantianTerpenuhi: bool,
     *         targetPulang: string|null,
     *         batasPulang: string|null,
     *         catatanPenggantian: string|null,
     *         menitPulangCepat: int|null,
     *         levelPsw: string|null,
     *         persenPsw: float,
     *         persenTotal: float
     *     }>,
     *     ringkasan: array{
     *         jumlahHari: int,
     *         jumlahTl1: int,
     *         jumlahTl2: int,
     *         jumlahTl3: int,
     *         jumlahPsw1: int,
     *         jumlahPsw2: int,
     *         jumlahPsw3: int,
     *         jumlahPsw4: int,
     *         jumlahPenggantian: int,
     *         totalMenitTerlambat: int,
     *         totalMenitDiganti: int,
     *         totalMenitPulangCepat: int,
     *         totalPersenTl: float,
     *         totalPersenPsw: float,
     *         totalPersen: float
     *     }
     * }
     */
    public function __invoke(Pegawai $pegawai, CarbonInterface $mulai, CarbonInterface $selesai): array
    {
        $mulai = CarbonImmutable::parse($mulai->format('Y-m-d'))->startOfDay();
        $selesai = CarbonImmutable::parse($selesai->format('Y-m-d'))->startOfDay();

        $presensi = Presensi::query()
            ->with(['jamKerja', 'pengajuan', 'penggantianKeterlambatan'])
            ->where('id_pegawai', $pegawai->id)
            ->whereBetween('tgl_presensi', [$mulai->toDateString(), $selesai->toDateString()])
            ->orderBy('tgl_presensi')
            ->get();

        return $this->ringkas($presensi, $mulai, $selesai);
    }

    /**
     * @return array<string, mixed>
     */
    public function bulanan(Pegawai $pegawai, int $tahun, int $bulan): array
    {
        $mulai = CarbonImmutable::create($tahun, $bulan, 1)->startOfMonth();

        $hasil = ($this)($pegawai, $mulai, $mulai->endOfMonth());

        return [
            ...$hasil,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tahunan(Pegawai $pegawai, int $tahun): array
    {
        $mulai = CarbonImmutable::create($tahun, 1, 1)->startOfYear();

        $hasil = ($this)($pegawai, $mulai, $mulai->endOfYear());

        return [
            ...$hasil,
            'tahun' => $tahun,
        ];
    }

    /**
     * @param  Collection<int, Presensi>  $presensi
     * @return array{
     *     mulai: string,
     *     selesai: string,
     *     baris: list<array{
     *         tanggal: string,
     *         menitTerlambat: int|null,
     *         levelTl: string|null,
     *         persenTl: float,
     *         menitDiganti: int,
     *         bisaDiganti: bool,
     *         penggantianTerpenuhi: bool,
     *         targetPulang: string|null,
     *         batasPulang: string|null,
     *         catatanPenggantian: string|null,
     *         menitPulangCepat: int|null,
     *         levelPsw: string|null,
     *         persenPsw: float,
     *         persenTotal: float
     *     }>,
     *     ringkasan: array{
     *         jumlahHari: int,
     *         jumlahTl1: int,
     *         jumlahTl2: int,
     *         jumlahTl3: int,
     *         jumlahPsw1: int,
     *         jumlahPsw2: int,
     *         jumlahPsw3: int,
     *         jumlahPsw4: int,
     *         jumlahPenggantian: int,
     *         totalMenitTerlambat: int,
     *         totalMenitDiganti: int,
     *         totalMenitPulangCepat: int,
     *         totalPersenTl: float,
     *         totalPersenPsw: float,
     *         totalPersen: float
     *     }
     * }
     */
    private function ringkas(Collection $presensi, CarbonImmutable $mulai, CarbonImmutable $selesai): array
    {
        $baris = [];
        $jumlah = [
            'TL1' => 0,
            'TL2' => 0,
            'TL3' => 0,
            'PSW1' => 0,
            'PSW2' => 0,
            'PSW3' => 0,
            'PSW4' => 0,
        ];
        $totalMenitTerlambat = 0;
        $totalMenitPulangCepat = 0;
        $jumlahPenggantian = 0;
        $totalMenitDiganti = 0;

        foreach ($presensi as $item) {
            $hasil = $this->hitungBaris($item);

            if ($hasil === null) {
                continue;
            }

            if ($hasil['levelTl'] !== null) {
                $jumlah[$hasil['levelTl']]++;
            }

            if ($hasil['levelPsw'] !== null) {
                $jumlah[$hasil['levelPsw']]++;
            }

            $totalMenitTerlambat += $hasil['menitTerlambat'] ?? 0;
            $totalMenitPulangCepat += $hasil['menitPulangCepat'] ?? 0;

            if ($hasil['penggantianTerpenuhi']) {
                $jumlahPenggantian++;
                $totalMenitDiganti += $hasil['menitDiganti'];
            }

            $baris[] = $hasil;
        }

        $totalPersenTl = round(array_sum(array_column($baris, 'persenTl')), 2);
        $totalPersenPsw = round(array_sum(array_column($baris, 'persenPsw')), 2);

        return [
            'mulai' => $mulai->toDateString(),
            'selesai' => $selesai->toDateString(),
            'baris' => $baris,
            'ringkasan' => [
                'jumlahHari' => count($baris),
                'jumlahTl1' => $jumlah['TL1'],
                'jumlahTl2' => $jumlah['TL2'],
                'jumlahTl3' => $jumlah['TL3'],
                'jumlahPsw1' => $jumlah['PSW1'],
                'jumlahPsw2' => $jumlah['PSW2'],
                'jumlahPsw3' => $jumlah['PSW3'],
                'jumlahPsw4' => $jumlah['PSW4'],
                'jumlahPenggantian' => $jumlahPenggantian,
                'totalMenitTerlambat' => $totalMenitTerlambat,
                'totalMenitDiganti' => $totalMenitDiganti,
                'totalMenitPulangCepat' => $totalMenitPulangCepat,
                'totalPersenTl' => $totalPersenTl,
                'totalPersenPsw' => $totalPersenPsw,
                'totalPersen' => round($totalPersenTl + $totalPersenPsw, 2),
            ],
        ];
    }

    /**
     * @return array{
     *     tanggal: string,
     *     menitTerlambat: int|null,
     *     levelTl: string|null,
     *     persenTl: float,
     *     menitPulangCepat: int|null,
     *     levelPsw: string|null,
     *     persenPsw: float,
     *     persenTotal: float
     * }|null
     */
    private function hitungBaris(Presensi $presensi): ?array
    {
        if ($presensi->status !== 'hadir') {
            return null;
        }

        $shift = $presensi->jamKerja;

        if ($shift === null || in_array($shift->kode, $this->kodeJamKerjaDiabaikan(), true)) {
            return null;
        }

        if ($this->dinasLuarSetengahHari($presensi)) {
            return null;
        }

        $penggantian = ($this->hitungPenggantian)($presensi);
        [$levelTl, $menitTerlambat] = $this->keterlambatan($presensi, $penggantian);
        [$levelPsw, $menitPulangCepat] = $this->pulangCepat($presensi, $shift->jam_pulang);

        return [
            'tanggal' => (string) $presensi->tgl_presensi,
            'menitTerlambat' => $menitTerlambat,
            'levelTl' => $levelTl,
            'persenTl' => $levelTl === null ? 0.0 : $this->persenUntuk('keterlambatan', $levelTl),
            'menitDiganti' => $penggantian['menitDiganti'] ?? 0,
            'bisaDiganti' => $penggantian['bisaDiganti'] ?? false,
            'penggantianTerpenuhi' => $penggantian['terpenuhi'] ?? false,
            'targetPulang' => $penggantian['targetPulang'] ?? null,
            'batasPulang' => $penggantian['batasPulang'] ?? null,
            'catatanPenggantian' => $penggantian['catatan'] ?? null,
            'menitPulangCepat' => $menitPulangCepat,
            'levelPsw' => $levelPsw,
            'persenPsw' => $levelPsw === null ? 0.0 : $this->persenUntuk('pulang_cepat', $levelPsw),
            'persenTotal' => round(
                ($levelTl === null ? 0.0 : $this->persenUntuk('keterlambatan', $levelTl))
                + ($levelPsw === null ? 0.0 : $this->persenUntuk('pulang_cepat', $levelPsw)),
                2,
            ),
        ];
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    /**
     * @param  array<string, mixed>|null  $penggantian
     * @return array{0: string|null, 1: int|null}
     */
    private function keterlambatan(Presensi $presensi, ?array $penggantian): array
    {
        // Hadir tanpa jam masuk dianggap tidak mengisi daftar hadir masuk.
        if ($presensi->jam_masuk === null) {
            return [self::LEVEL_TANPA_MASUK, null];
        }

        $menit = ($this->hitungMenitTerlambat)($presensi);

        if ($menit <= 0) {
            return [null, 0];
        }

        // Penggantian hari yang sama yang memenuhi jendela menghapus TL.
        if (($penggantian['terpenuhi'] ?? false) === true) {
            return [null, $menit];
        }

        return [$this->tingkat('keterlambatan', $menit)['level'] ?? null, $menit];
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function pulangCepat(Presensi $presensi, ?string $jamPulang): array
    {
        if ($jamPulang === null || $jamPulang === '') {
            return [null, null];
        }

        if ($presensi->jam_keluar === null) {
            return $this->belumAbsenPulang($presensi, $jamPulang)
                ? [self::LEVEL_TANPA_PULANG, null]
                : [null, null];
        }

        $batas = $this->waktu($presensi->tgl_presensi, $jamPulang);
        $keluar = $this->waktu($presensi->tgl_presensi, $presensi->jam_keluar);

        if ($keluar->gte($batas)) {
            return [null, 0];
        }

        $menit = (int) ceil(($batas->getTimestamp() - $keluar->getTimestamp()) / 60);

        return [$this->tingkat('pulang_cepat', $menit)['level'] ?? null, $menit];
    }

    /**
     * Baris hadir tanpa jam keluar baru dianggap PSW4 setelah shift berakhir.
     * Hari ini yang belum melewati jam pulang belum bisa diputuskan.
     */
    private function belumAbsenPulang(Presensi $presensi, string $jamPulang): bool
    {
        $tanggal = CarbonImmutable::parse((string) $presensi->tgl_presensi)->startOfDay();
        $sekarang = $this->sekarang();

        if ($tanggal->lt($sekarang->startOfDay())) {
            return true;
        }

        if ($tanggal->gt($sekarang->startOfDay())) {
            return false;
        }

        return $sekarang->gte($this->waktu($presensi->tgl_presensi, $jamPulang));
    }

    private function dinasLuarSetengahHari(Presensi $presensi): bool
    {
        $pengajuan = $presensi->pengajuan;

        return $pengajuan !== null
            && $pengajuan->jenis === 'dl'
            && $pengajuan->mode_dl === Pengajuan::MODE_DL_SETENGAH;
    }

    /**
     * @return Collection<int, array{level: string, min: int, max: int|null, persen: float}>
     */
    private function daftarTingkat(string $kunci): Collection
    {
        return collect(config("tunjangan.{$kunci}", []));
    }

    /**
     * @return array{level: string, min: int, max: int|null, persen: float}|null
     */
    private function tingkat(string $kunci, int $menit): ?array
    {
        return $this->daftarTingkat($kunci)->first(function (array $tingkat) use ($menit): bool {
            return $menit >= $tingkat['min']
                && ($tingkat['max'] === null || $menit <= $tingkat['max']);
        });
    }

    private function persenUntuk(string $kunci, string $level): float
    {
        $tingkat = $this->daftarTingkat($kunci)->firstWhere('level', $level);

        return (float) ($tingkat['persen'] ?? 0.0);
    }

    private function waktu(string $tanggal, string $jam): CarbonImmutable
    {
        return CarbonImmutable::parse("{$tanggal} {$jam}", (string) config('presensi.zona_waktu', 'Asia/Jakarta'));
    }

    private function sekarang(): CarbonImmutable
    {
        return CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
    }

    /**
     * @return list<string>
     */
    private function kodeJamKerjaDiabaikan(): array
    {
        $kode = config('presensi.kode_jam_kerja_diabaikan', []);

        return is_array($kode) ? array_values($kode) : [];
    }
}
