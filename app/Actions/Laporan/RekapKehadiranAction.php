<?php

namespace App\Actions\Laporan;

use App\Actions\Presensi\HitungMenitTerlambatAction;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RekapKehadiranAction
{
    public function __construct(
        private readonly HitungMenitTerlambatAction $hitungMenitTerlambat,
    ) {}

    /**
     * Nama bulan Indonesia. Tidak memakai locale Carbon supaya tampilan
     * tidak bergantung pada ekstensi/locale server.
     *
     * @var array<int, string>
     */
    private const BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Rekap dashboard pegawai: kartu hari ini, ringkasan bulan berjalan, dan
     * total keterlambatan tahun berjalan.
     *
     * Semantik angka (keputusan QA, issue #34): `tepatWaktu` dan `terlambat`
     * adalah dua keranjang terpisah. Lima angka rekap bulan (tepatWaktu,
     * terlambat, cuti, sakit, dl) tidak saling tumpang tindih sehingga
     * jumlahnya sama dengan total baris presensi bulan itu.
     *
     * Baris `hadir` tanpa jam masuk atau tanpa shift tidak bisa dibuktikan
     * terlambat, jadi dihitung sebagai tepat waktu.
     *
     * @return array{
     *     hariIni: array{
     *         tanggal: string,
     *         labelTanggal: string,
     *         shift: array{kode: string, nama: string, jamMasuk: string, jamPulang: string}|null,
     *         status: string|null,
     *         statusLabel: string|null,
     *         jamMasuk: string|null,
     *         jamKeluar: string|null,
     *         menitTerlambat: int,
     *         terlambat: bool
     *     },
     *     bulanIni: array{
     *         label: string,
     *         tepatWaktu: int,
     *         terlambat: int,
     *         cuti: int,
     *         sakit: int,
     *         dl: int,
     *         totalHari: int,
     *         totalMenitTerlambat: int,
     *         totalMenitTerlambatTeks: string
     *     },
     *     tahunIni: array{
     *         tahun: int,
     *         hariTerlambat: int,
     *         totalMenitTerlambat: int,
     *         totalMenitTerlambatTeks: string
     *     }
     * }
     */
    public function __invoke(Pegawai $pegawai): array
    {
        $sekarang = $this->sekarang();
        $kodeDiabaikan = $this->kodeJamKerjaDiabaikan();

        $presensiTahun = Presensi::query()
            ->with(['jamKerja', 'pengajuan'])
            ->where('id_pegawai', $pegawai->id)
            ->whereBetween('tgl_presensi', [
                $sekarang->startOfYear()->toDateString(),
                $sekarang->endOfYear()->toDateString(),
            ])
            ->orderBy('tgl_presensi')
            ->get();

        $presensiBulan = $presensiTahun->filter(
            fn (Presensi $presensi): bool => str_starts_with(
                (string) $presensi->tgl_presensi,
                $sekarang->format('Y-m'),
            ),
        );

        return [
            'hariIni' => $this->ringkasHariIni(
                $sekarang,
                $this->shiftHariIni($pegawai, $sekarang),
                $presensiTahun->firstWhere('tgl_presensi', $sekarang->toDateString()),
                $kodeDiabaikan,
            ),
            'bulanIni' => $this->ringkasBulanan($presensiBulan, $sekarang, $kodeDiabaikan),
            'tahunIni' => $this->ringkasTahunan($presensiTahun, $sekarang, $kodeDiabaikan),
        ];
    }

    /**
     * Data kartu "hari ini" saja — dipakai halaman presensi (#31) supaya
     * status masuk/keluar dan shift berasal dari perhitungan yang sama
     * dengan dashboard (#34).
     *
     * @return array{
     *     tanggal: string,
     *     labelTanggal: string,
     *     shift: array{kode: string, nama: string, jamMasuk: string, jamPulang: string}|null,
     *     status: string|null,
     *     statusLabel: string|null,
     *     jamMasuk: string|null,
     *     jamKeluar: string|null,
     *     menitTerlambat: int,
     *     terlambat: bool
     * }
     */
    public function hariIni(Pegawai $pegawai): array
    {
        $sekarang = $this->sekarang();

        $presensi = Presensi::query()
            ->with(['jamKerja', 'pengajuan'])
            ->where('id_pegawai', $pegawai->id)
            ->where('tgl_presensi', $sekarang->toDateString())
            ->first();

        return $this->ringkasHariIni(
            $sekarang,
            $this->shiftHariIni($pegawai, $sekarang),
            $presensi,
            $this->kodeJamKerjaDiabaikan(),
        );
    }

    /**
     * Ringkasan satu bulan dari baris presensi yang sudah difilter.
     *
     * Dipakai dashboard (#34). Rekap semua pegawai (#44) dapat mengelompokkan
     * baris per pegawai lalu memanggil metode ini supaya angka dashboard dan
     * halaman rekap berasal dari perhitungan yang sama. Baris wajib sudah
     * memuat relasi `jamKerja` (eager load) agar tidak memicu N+1.
     *
     * @param  Collection<int, Presensi>  $presensiBulan
     * @param  list<string>|null  $kodeDiabaikan  Default: ambil dari config/presensi.php
     * @return array{
     *     label: string,
     *     tepatWaktu: int,
     *     terlambat: int,
     *     cuti: int,
     *     sakit: int,
     *     dl: int,
     *     totalHari: int,
     *     totalMenitTerlambat: int,
     *     totalMenitTerlambatTeks: string
     * }
     */
    public function ringkasBulanan(
        Collection $presensiBulan,
        CarbonInterface $bulan,
        ?array $kodeDiabaikan = null,
    ): array {
        $kodeDiabaikan ??= $this->kodeJamKerjaDiabaikan();

        $tepatWaktu = 0;
        $terlambat = 0;
        $cuti = 0;
        $sakit = 0;
        $dl = 0;
        $totalMenitTerlambat = 0;

        foreach ($presensiBulan as $presensi) {
            $menit = $this->menitTerlambat($presensi, $kodeDiabaikan);

            if ($presensi->status === 'hadir') {
                if ($menit > 0) {
                    $terlambat++;
                    $totalMenitTerlambat += $menit;
                } else {
                    $tepatWaktu++;
                }

                continue;
            }

            if ($presensi->status === 'cuti') {
                $cuti++;
            } elseif ($presensi->status === 'sakit') {
                $sakit++;
            } elseif ($presensi->status === 'dl') {
                $dl++;
            }
        }

        return [
            'label' => self::BULAN[$bulan->month].' '.$bulan->year,
            'tepatWaktu' => $tepatWaktu,
            'terlambat' => $terlambat,
            'cuti' => $cuti,
            'sakit' => $sakit,
            'dl' => $dl,
            'totalHari' => $presensiBulan->count(),
            'totalMenitTerlambat' => $totalMenitTerlambat,
            'totalMenitTerlambatTeks' => $this->formatMenit($totalMenitTerlambat),
        ];
    }

    /**
     * Total keterlambatan satu tahun berjalan.
     *
     * @param  Collection<int, Presensi>  $presensiTahun
     * @param  list<string>|null  $kodeDiabaikan  Default: ambil dari config/presensi.php
     * @return array{
     *     tahun: int,
     *     hariTerlambat: int,
     *     totalMenitTerlambat: int,
     *     totalMenitTerlambatTeks: string
     * }
     */
    public function ringkasTahunan(
        Collection $presensiTahun,
        CarbonInterface $tahun,
        ?array $kodeDiabaikan = null,
    ): array {
        $kodeDiabaikan ??= $this->kodeJamKerjaDiabaikan();

        $hariTerlambat = 0;
        $totalMenitTerlambat = 0;

        foreach ($presensiTahun as $presensi) {
            $menit = $this->menitTerlambat($presensi, $kodeDiabaikan);

            if ($menit > 0) {
                $hariTerlambat++;
                $totalMenitTerlambat += $menit;
            }
        }

        return [
            'tahun' => $tahun->year,
            'hariTerlambat' => $hariTerlambat,
            'totalMenitTerlambat' => $totalMenitTerlambat,
            'totalMenitTerlambatTeks' => $this->formatMenit($totalMenitTerlambat),
        ];
    }

    /**
     * Menit keterlambatan satu baris presensi. Perhitungan aslinya ada di
     * HitungMenitTerlambatAction agar dipakai bersama histori (#33) dan
     * rekap semua pegawai (#44).
     *
     * @param  list<string>  $kodeDiabaikan
     */
    private function menitTerlambat(Presensi $presensi, array $kodeDiabaikan): int
    {
        return ($this->hitungMenitTerlambat)($presensi, $kodeDiabaikan);
    }

    /**
     * Shift yang dijadwalkan untuk pegawai pada hari ini, bila ada.
     */
    private function shiftHariIni(Pegawai $pegawai, CarbonInterface $sekarang): ?JamKerja
    {
        $namaHari = KonfigurasiJamKerja::HARI[$sekarang->dayOfWeekIso - 1];

        return KonfigurasiJamKerja::query()
            ->with('jamKerja')
            ->where('id_pegawai', $pegawai->id)
            ->where('hari', $namaHari)
            ->first()
            ?->jamKerja;
    }

    /**
     * Tanggal absensi dinilai pada zona waktu kantor, bukan zona waktu
     * aplikasi (config/app.php masih UTC). Lihat config/presensi.php.
     */
    private function sekarang(): CarbonImmutable
    {
        return CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
    }

    /**
     * @param  array{kode: string, nama: string, jamMasuk: string, jamPulang: string}|null  $shift
     * @param  list<string>  $kodeDiabaikan
     * @return array{
     *     tanggal: string,
     *     labelTanggal: string,
     *     shift: array{kode: string, nama: string, jamMasuk: string, jamPulang: string}|null,
     *     status: string|null,
     *     statusLabel: string|null,
     *     jamMasuk: string|null,
     *     jamKeluar: string|null,
     *     menitTerlambat: int,
     *     terlambat: bool
     * }
     */
    private function ringkasHariIni(
        CarbonImmutable $sekarang,
        ?JamKerja $shift,
        ?Presensi $presensi,
        array $kodeDiabaikan,
    ): array {
        $menitTerlambat = $presensi === null ? 0 : $this->menitTerlambat($presensi, $kodeDiabaikan);

        return [
            'tanggal' => $sekarang->toDateString(),
            'labelTanggal' => implode(', ', [
                KonfigurasiJamKerja::HARI[$sekarang->dayOfWeekIso - 1],
                $sekarang->day.' '.self::BULAN[$sekarang->month].' '.$sekarang->year,
            ]),
            'shift' => $shift === null ? null : [
                'kode' => $shift->kode,
                'nama' => $shift->nama,
                'jamMasuk' => $this->formatJam($shift->jam_masuk) ?? '-',
                'jamPulang' => $this->formatJam($shift->jam_pulang) ?? '-',
            ],
            'status' => $presensi?->status,
            'statusLabel' => $presensi === null
                ? null
                : (Presensi::LABEL_STATUS[$presensi->status] ?? ucfirst($presensi->status)),
            'jamMasuk' => $this->formatJam($presensi?->jam_masuk),
            'jamKeluar' => $this->formatJam($presensi?->jam_keluar),
            'menitTerlambat' => $menitTerlambat,
            'terlambat' => $menitTerlambat > 0,
        ];
    }

    private function formatJam(?string $jam): ?string
    {
        if ($jam === null || $jam === '') {
            return null;
        }

        return Carbon::parse($jam)->format('H:i');
    }

    private function formatMenit(int $menit): string
    {
        if ($menit <= 0) {
            return '0 menit';
        }

        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        if ($jam === 0) {
            return $sisa.' menit';
        }

        if ($sisa === 0) {
            return $jam.' jam';
        }

        return $jam.' jam '.$sisa.' menit';
    }

    /**
     * Kode shift yang sengaja tidak dihitung terlambat (mis. JK03 untuk
     * lembur/akhir pekan pada data lama). Dapat diubah lewat .env.
     *
     * @return list<string>
     */
    private function kodeJamKerjaDiabaikan(): array
    {
        $kode = config('presensi.kode_jam_kerja_diabaikan', []);

        return is_array($kode) ? array_values($kode) : [];
    }
}
