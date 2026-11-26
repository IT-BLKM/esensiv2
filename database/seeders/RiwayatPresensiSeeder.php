<?php

namespace Database\Seeders;

use App\Actions\Presensi\HitungPenggantianKeterlambatanAction;
use App\Models\Cuti;
use App\Models\HariLibur;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\PenggantianKeterlambatan;
use App\Models\Presensi;
use App\Models\SaldoCuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Isi riwayat presensi tiga bulan untuk seluruh pegawai demo.
 *
 * Bulan kalender sebelumnya sengaja penuh: setiap pegawai punya baris di semua
 * hari kerja dengan pola berulang 12 hari yang menjamin munculnya hadir,
 * terlambat (berbagai tingkat, ada yang diganti), dinas luar full/setengah,
 * sakit, dan cuti.
 *
 * Seeder ini idempoten dan tidak menimpa baris yang sudah dibuat
 * AktivitasSeeder (skenario demo utama).
 */
class RiwayatPresensiSeeder extends Seeder
{
    private const JUMLAH_BULAN = 3;

    private const POLA_HARI = 12;

    private User $admin;

    private Cuti $cutiTahunan;

    private JamKerja $jk01;

    private JamKerja $jk02;

    private HitungPenggantianKeterlambatanAction $hitungPenggantian;

    private ?string $foto = null;

    private string $koordinat = '';

    public function run(): void
    {
        $this->admin = User::query()->where('email', 'admin@esensi.test')->firstOrFail();
        $this->cutiTahunan = Cuti::query()->where('kode', Cuti::KODE_TAHUNAN)->firstOrFail();
        $this->jk01 = JamKerja::query()->where('kode', 'JK01')->firstOrFail();
        $this->jk02 = JamKerja::query()->where('kode', 'JK02')->firstOrFail();
        $this->hitungPenggantian = app(HitungPenggantianKeterlambatanAction::class);
        $this->foto = $this->siapkanFotoSeed();
        $this->koordinat = (string) config('presensi.latitude').','.(string) config('presensi.longitude');

        $pegawai = Pegawai::query()
            ->with('user')
            ->orderBy('id')
            ->get();

        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->startOfDay();

        $this->seedKonfigurasi($pegawai);
        $this->seedSaldoCuti($pegawai);

        $mulai = $sekarang->startOfMonth()->subMonths(self::JUMLAH_BULAN - 1);
        $sudahAda = Presensi::query()
            ->whereIn('id_pegawai', $pegawai->pluck('id')->all())
            ->whereBetween('tgl_presensi', [$mulai->toDateString(), $sekarang->toDateString()])
            ->get(['id_pegawai', 'tgl_presensi'])
            ->mapWithKeys(fn (Presensi $presensi): array => [
                $presensi->id_pegawai.'|'.(string) $presensi->tgl_presensi => true,
            ])
            ->all();
        $hariLibur = array_fill_keys(HariLibur::dalamRentang($mulai, $sekarang), true);

        foreach ($pegawai as $index => $item) {
            $hariKe = 0;

            for ($mundur = self::JUMLAH_BULAN - 1; $mundur >= 0; $mundur--) {
                $awal = $sekarang->startOfMonth()->subMonths($mundur);
                $akhir = $mundur === 0 ? $sekarang->subDay() : $awal->endOfMonth();

                if ($akhir->lt($awal)) {
                    continue;
                }

                for ($tanggal = $awal; $tanggal->lte($akhir); $tanggal = $tanggal->addDay()) {
                    if ($tanggal->isWeekend() || isset($hariLibur[$tanggal->toDateString()])) {
                        continue;
                    }

                    $hariKe++;
                    $kunci = $item->id.'|'.$tanggal->toDateString();

                    if (isset($sudahAda[$kunci])) {
                        continue;
                    }

                    $this->seedHari($item, $index, $hariKe, $tanggal);
                }
            }
        }
    }

    /**
     * @param  Collection<int, Pegawai>  $pegawai
     */
    private function seedKonfigurasi(Collection $pegawai): void
    {
        foreach ($pegawai as $item) {
            foreach (['Senin', 'Selasa', 'Rabu', 'Kamis'] as $hari) {
                KonfigurasiJamKerja::updateOrCreate(
                    ['id_pegawai' => $item->id, 'hari' => $hari],
                    ['id_jam_kerja' => $this->jk01->id],
                );
            }

            KonfigurasiJamKerja::updateOrCreate(
                ['id_pegawai' => $item->id, 'hari' => 'Jumat'],
                ['id_jam_kerja' => $this->jk02->id],
            );
        }
    }

    /**
     * @param  Collection<int, Pegawai>  $pegawai
     */
    private function seedSaldoCuti(Collection $pegawai): void
    {
        $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;
        $daftarCuti = Cuti::query()->get();

        foreach ($pegawai as $item) {
            foreach ($daftarCuti as $cuti) {
                SaldoCuti::updateOrCreate(
                    [
                        'id_pegawai' => $item->id,
                        'id_cuti' => $cuti->id,
                        'tahun_sumber' => $tahun,
                    ],
                    ['saldo' => 0],
                );

                if ($cuti->kode === Cuti::KODE_TAHUNAN) {
                    SaldoCuti::updateOrCreate(
                        [
                            'id_pegawai' => $item->id,
                            'id_cuti' => $cuti->id,
                            'tahun_sumber' => $tahun - 1,
                        ],
                        ['saldo' => 6],
                    );
                }
            }
        }
    }

    private function seedHari(Pegawai $pegawai, int $index, int $hariKe, CarbonImmutable $tanggal): void
    {
        $pola = ($index + $hariKe + $tanggal->month) % self::POLA_HARI;

        if ($pola === 0) {
            $pengajuan = $this->simpanPengajuan($pegawai, $tanggal, 'cuti', $this->cutiTahunan->id, null);
            $this->simpanPresensi($pegawai, $tanggal, null, [
                'status' => 'cuti',
                'id_pengajuan' => $pengajuan->id,
            ]);

            return;
        }

        if ($pola === 1) {
            $pengajuan = $this->simpanPengajuan($pegawai, $tanggal, 'sakit', null, null);
            $this->simpanPresensi($pegawai, $tanggal, null, [
                'status' => 'sakit',
                'id_pengajuan' => $pengajuan->id,
            ]);

            return;
        }

        $shift = $this->shiftUntuk($tanggal);

        if ($pola === 2) {
            $mode = (($index + $hariKe) % 2 === 0)
                ? Pengajuan::MODE_DL_FULL
                : Pengajuan::MODE_DL_SETENGAH;
            $pengajuan = $this->simpanPengajuan($pegawai, $tanggal, 'dl', null, $mode);

            if ($mode === Pengajuan::MODE_DL_FULL) {
                $this->simpanPresensi($pegawai, $tanggal, null, [
                    'status' => 'dl',
                    'id_pengajuan' => $pengajuan->id,
                ]);
            } else {
                $this->simpanPresensi($pegawai, $tanggal, $shift, [
                    'status' => 'hadir',
                    'id_pengajuan' => $pengajuan->id,
                    'jam_masuk' => $this->jamMasuk($tanggal, $shift),
                    'jam_keluar' => $this->jamKeluar($tanggal, $shift, 5),
                    'koordinat_masuk' => $this->koordinat,
                    'koordinat_keluar' => $this->koordinat,
                    'foto_masuk' => $this->foto,
                    'foto_keluar' => $this->foto,
                ]);
            }

            return;
        }

        if (in_array($pola, [3, 4, 5], true)) {
            $menitTerlambat = [5, 15, 20, 31, 45, 61][($index + $hariKe) % 6];
            $diganti = $menitTerlambat <= 30 && (($index + $hariKe) % 2 === 0);

            $this->simpanPresensi($pegawai, $tanggal, $shift, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($tanggal, $shift, $menitTerlambat),
                'jam_keluar' => $diganti
                    ? $this->jamKeluar($tanggal, $shift, $menitTerlambat + 2)
                    : $this->jamKeluar($tanggal, $shift, 5),
                'koordinat_masuk' => $this->koordinat,
                'koordinat_keluar' => $this->koordinat,
                'foto_masuk' => $this->foto,
                'foto_keluar' => $this->foto,
            ]);

            return;
        }

        $pulangCepat = (($index * 3 + $hariKe) % 10) === 0;

        $this->simpanPresensi($pegawai, $tanggal, $shift, [
            'status' => 'hadir',
            'jam_masuk' => $this->jamMasuk($tanggal, $shift, [-3, 0, 2, 4][($index + $hariKe) % 4]),
            'jam_keluar' => $pulangCepat
                ? $this->jamKeluar($tanggal, $shift, -[20, 35, 50][($index + $hariKe) % 3])
                : $this->jamKeluar($tanggal, $shift, [1, 3, 5][($index + $hariKe) % 3]),
            'koordinat_masuk' => $this->koordinat,
            'koordinat_keluar' => $this->koordinat,
            'foto_masuk' => $this->foto,
            'foto_keluar' => $this->foto,
        ]);
    }

    private function simpanPengajuan(
        Pegawai $pegawai,
        CarbonImmutable $tanggal,
        string $jenis,
        ?int $idCuti,
        ?string $modeDl,
    ): Pengajuan {
        $keterangan = match ($jenis) {
            'cuti' => 'Cuti tahunan (data demo)',
            'sakit' => 'Sakit dengan surat dokter (data demo)',
            default => $modeDl === Pengajuan::MODE_DL_SETENGAH
                ? 'Dinas luar setengah hari (data demo)'
                : 'Dinas luar penuh (data demo)',
        };

        return Pengajuan::updateOrCreate(
            [
                'id_pegawai' => $pegawai->id,
                'jenis' => $jenis,
                'tanggal_mulai' => $tanggal->toDateString(),
                'tanggal_berakhir' => $tanggal->toDateString(),
            ],
            [
                'id_cuti' => $idCuti,
                'mode_dl' => $modeDl,
                'keterangan' => $keterangan,
                'lampiran' => null,
                'status' => 'approved',
                'disetujui_oleh' => $this->admin->id,
                'disetujui_pada' => $tanggal->setTime(8, 0),
                'catatan' => null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function simpanPresensi(
        Pegawai $pegawai,
        CarbonImmutable $tanggal,
        ?JamKerja $shift,
        array $atribut,
    ): Presensi {
        $presensi = Presensi::updateOrCreate(
            [
                'id_pegawai' => $pegawai->id,
                'tgl_presensi' => $tanggal->toDateString(),
            ],
            array_merge([
                'id_jam_kerja' => $shift?->id,
                'id_pengajuan' => null,
                'jam_masuk' => null,
                'jam_keluar' => null,
                'foto_masuk' => null,
                'foto_keluar' => null,
                'koordinat_masuk' => null,
                'koordinat_keluar' => null,
                'status' => 'hadir',
            ], $atribut),
        );

        if ($shift === null || $presensi->status !== 'hadir' || $presensi->jam_keluar === null) {
            return $presensi;
        }

        $hasil = ($this->hitungPenggantian)($presensi->refresh(), $shift);

        if ($hasil === null || $hasil['jamSelesai'] === null) {
            return $presensi;
        }

        PenggantianKeterlambatan::updateOrCreate(
            ['id_presensi' => $presensi->id],
            [
                'tanggal' => $hasil['tanggal'],
                'jam_mulai' => $hasil['jamMulai'],
                'jam_selesai' => $hasil['jamSelesai'],
                'target_pulang' => $hasil['targetPulang'],
                'batas_pulang' => $hasil['batasPulang'],
                'menit_terlambat' => $hasil['menitTerlambat'],
                'menit_diganti' => $hasil['menitDiganti'],
                'bisa_diganti' => $hasil['bisaDiganti'],
                'terpenuhi' => $hasil['terpenuhi'],
                'catatan' => $hasil['catatan'],
            ],
        );

        return $presensi;
    }

    private function shiftUntuk(CarbonImmutable $tanggal): JamKerja
    {
        if ($tanggal->isFriday()) {
            return $this->jk02;
        }

        return $this->jk01;
    }

    private function jamMasuk(CarbonImmutable $tanggal, JamKerja $shift, int $tambahMenit = 0): string
    {
        return CarbonImmutable::parse($tanggal->toDateString().' '.$shift->jam_masuk)
            ->addMinutes($tambahMenit)
            ->format('H:i:s');
    }

    private function jamKeluar(CarbonImmutable $tanggal, JamKerja $shift, int $tambahMenit = 0): string
    {
        return CarbonImmutable::parse($tanggal->toDateString().' '.$shift->jam_pulang)
            ->addMinutes($tambahMenit)
            ->format('H:i:s');
    }

    private function siapkanFotoSeed(): ?string
    {
        if (! app()->environment('local', 'development')) {
            return null;
        }

        $sumber = public_path('images/logo-esensi-transparan.png');

        if (! is_file($sumber)) {
            return null;
        }

        Storage::disk('local')->put('presensi/seed/foto.png', (string) file_get_contents($sumber));

        return 'presensi/seed/foto.png';
    }
}
