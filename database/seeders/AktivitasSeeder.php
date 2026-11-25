<?php

namespace Database\Seeders;

use App\Actions\Presensi\HitungPenggantianKeterlambatanAction;
use App\Models\Cuti;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\PenggantianKeterlambatan;
use App\Models\Presensi;
use App\Models\SaldoCuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AktivitasSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@esensi.test')->firstOrFail();
        $jk01 = JamKerja::query()->where('kode', 'JK01')->firstOrFail();
        $jk02 = JamKerja::query()->where('kode', 'JK02')->firstOrFail();
        $jk03 = JamKerja::query()->where('kode', 'JK03')->firstOrFail();

        $pegawai = Pegawai::query()
            ->with('user')
            ->whereIn('nip', [
                '199302032022031001',
                '199401012019012002',
                '199505152020122003',
                '199806202021012004',
                '199207112018011005',
            ])
            ->get()
            ->keyBy(fn (Pegawai $item): string => (string) $item->user?->email);

        $cuti = Cuti::query()->get()->keyBy('kode');
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->startOfDay();

        $this->seedKonfigurasi($pegawai, $jk01, $jk02, $jk03);
        $this->seedSaldoCuti($pegawai, $cuti);

        $foto = $this->siapkanFotoSeed();
        $tanggalBulanIni = array_values(array_filter(
            $this->hariKerjaSebelum($sekarang, 3),
            fn (CarbonImmutable $tanggal): bool => $tanggal->year === $sekarang->year
                && $tanggal->month === $sekarang->month,
        ));
        $tanggalBulanLalu = $this->hariKerjaSebelum($sekarang->startOfMonth(), 3);
        $akhirPekan = $this->akhirPekanSebelum($sekarang->startOfMonth());

        $pengajuan = $this->seedPengajuan($admin, $pegawai, $cuti, $sekarang, $tanggalBulanIni, $tanggalBulanLalu);

        $this->seedPresensi(
            $pegawai,
            $pengajuan,
            $jk01,
            $jk02,
            $jk03,
            $sekarang,
            $tanggalBulanIni,
            $tanggalBulanLalu,
            $akhirPekan,
            $foto,
        );
    }

    /**
     * @param  Collection<string, Pegawai>  $pegawai
     */
    private function seedKonfigurasi(Collection $pegawai, JamKerja $jk01, JamKerja $jk02, JamKerja $jk03): void
    {
        $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis'];
        $bolehAkhirPekan = ['pegawai@esensi.test', 'pegawai5@esensi.test'];

        foreach ($pegawai as $email => $item) {
            foreach ($hariKerja as $hari) {
                KonfigurasiJamKerja::updateOrCreate(
                    ['id_pegawai' => $item->id, 'hari' => $hari],
                    ['id_jam_kerja' => $jk01->id],
                );
            }

            KonfigurasiJamKerja::updateOrCreate(
                ['id_pegawai' => $item->id, 'hari' => 'Jumat'],
                ['id_jam_kerja' => $jk02->id],
            );

            if (in_array($email, $bolehAkhirPekan, true)) {
                foreach (['Sabtu', 'Minggu'] as $hari) {
                    KonfigurasiJamKerja::updateOrCreate(
                        ['id_pegawai' => $item->id, 'hari' => $hari],
                        ['id_jam_kerja' => $jk03->id],
                    );
                }
            }
        }
    }

    /**
     * @param  Collection<string, Pegawai>  $pegawai
     * @param  Collection<string, Cuti>  $cuti
     */
    private function seedSaldoCuti(Collection $pegawai, Collection $cuti): void
    {
        $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;

        foreach ($pegawai as $item) {
            foreach ($cuti as $cutiItem) {
                SaldoCuti::updateOrCreate(
                    [
                        'id_pegawai' => $item->id,
                        'id_cuti' => $cutiItem->id,
                        'tahun_sumber' => $tahun,
                    ],
                    ['saldo' => 0],
                );

                if ($cutiItem->kode === 'C01') {
                    SaldoCuti::updateOrCreate(
                        [
                            'id_pegawai' => $item->id,
                            'id_cuti' => $cutiItem->id,
                            'tahun_sumber' => $tahun - 1,
                        ],
                        ['saldo' => 6],
                    );
                }
            }
        }
    }

    /**
     * @param  Collection<string, Pegawai>  $pegawai
     * @param  Collection<string, Cuti>  $cuti
     * @param  list<CarbonImmutable>  $bulanIni
     * @param  list<CarbonImmutable>  $bulanLalu
     * @return array<string, Pengajuan>
     */
    private function seedPengajuan(
        User $admin,
        Collection $pegawai,
        Collection $cuti,
        CarbonImmutable $sekarang,
        array $bulanIni,
        array $bulanLalu,
    ): array {
        $c01 = $cuti->get('C01');
        $hasil = [];
        $hariIni = $sekarang->toDateString();
        $d1 = $bulanIni[0] ?? null;
        $d2 = $bulanIni[1] ?? null;
        $p1 = $bulanLalu[0] ?? null;

        $hasil['cuti_ahmad_hari_ini'] = $this->simpanPengajuan([
            'id_pegawai' => $pegawai['pegawai3@esensi.test']->id,
            'id_cuti' => $c01?->id,
            'tanggal_mulai' => $hariIni,
            'tanggal_berakhir' => $hariIni,
            'jenis' => 'cuti',
            'mode_dl' => null,
            'keterangan' => 'Cuti tahunan (seed hari ini)',
            'status' => 'approved',
            'disetujui_oleh' => $admin->id,
            'disetujui_pada' => now(),
            'catatan' => null,
        ]);

        $hasil['sakit_rina_hari_ini'] = $this->simpanPengajuan([
            'id_pegawai' => $pegawai['pegawai4@esensi.test']->id,
            'id_cuti' => null,
            'tanggal_mulai' => $hariIni,
            'tanggal_berakhir' => $hariIni,
            'jenis' => 'sakit',
            'mode_dl' => null,
            'keterangan' => 'Sakit dengan surat dokter (seed)',
            'status' => 'approved',
            'disetujui_oleh' => $admin->id,
            'disetujui_pada' => now(),
            'catatan' => null,
        ]);

        $hasil['dl_joko_hari_ini'] = $this->simpanPengajuan([
            'id_pegawai' => $pegawai['pegawai5@esensi.test']->id,
            'id_cuti' => null,
            'tanggal_mulai' => $hariIni,
            'tanggal_berakhir' => $hariIni,
            'jenis' => 'dl',
            'mode_dl' => 'setengah',
            'keterangan' => 'Dinas luar setengah hari (seed)',
            'status' => 'approved',
            'disetujui_oleh' => $admin->id,
            'disetujui_pada' => now(),
            'catatan' => null,
        ]);

        if ($d1 !== null) {
            $hasil['cuti_siti_bulan_ini'] = $this->simpanPengajuan([
                'id_pegawai' => $pegawai['pegawai2@esensi.test']->id,
                'id_cuti' => $c01?->id,
                'tanggal_mulai' => $d1->toDateString(),
                'tanggal_berakhir' => $d1->toDateString(),
                'jenis' => 'cuti',
                'mode_dl' => null,
                'keterangan' => 'Cuti tahunan (seed bulan ini)',
                'status' => 'approved',
                'disetujui_oleh' => $admin->id,
                'disetujui_pada' => now(),
                'catatan' => null,
            ]);

            $hasil['dl_joko_bulan_ini'] = $this->simpanPengajuan([
                'id_pegawai' => $pegawai['pegawai5@esensi.test']->id,
                'id_cuti' => null,
                'tanggal_mulai' => $d1->toDateString(),
                'tanggal_berakhir' => $d1->toDateString(),
                'jenis' => 'dl',
                'mode_dl' => 'full',
                'keterangan' => 'Dinas luar penuh (seed bulan ini)',
                'status' => 'approved',
                'disetujui_oleh' => $admin->id,
                'disetujui_pada' => now(),
                'catatan' => null,
            ]);
        }

        if ($p1 !== null && ($d1 === null || $p1->toDateString() !== $d1->toDateString())) {
            $hasil['cuti_siti_bulan_lalu'] = $this->simpanPengajuan([
                'id_pegawai' => $pegawai['pegawai2@esensi.test']->id,
                'id_cuti' => $c01?->id,
                'tanggal_mulai' => $p1->toDateString(),
                'tanggal_berakhir' => $p1->toDateString(),
                'jenis' => 'cuti',
                'mode_dl' => null,
                'keterangan' => 'Cuti tahunan (seed bulan lalu)',
                'status' => 'approved',
                'disetujui_oleh' => $admin->id,
                'disetujui_pada' => now(),
                'catatan' => null,
            ]);
        }

        $hasil['pending_cuti_budi'] = $this->simpanPengajuan([
            'id_pegawai' => $pegawai['pegawai@esensi.test']->id,
            'id_cuti' => $c01?->id,
            'tanggal_mulai' => $sekarang->addDays(10)->toDateString(),
            'tanggal_berakhir' => $sekarang->addDays(12)->toDateString(),
            'jenis' => 'cuti',
            'mode_dl' => null,
            'keterangan' => 'Cuti tahunan menunggu persetujuan (seed)',
            'status' => 'pending',
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
            'catatan' => null,
        ]);

        $hasil['pending_sakit_rina'] = $this->simpanPengajuan([
            'id_pegawai' => $pegawai['pegawai4@esensi.test']->id,
            'id_cuti' => null,
            'tanggal_mulai' => $sekarang->addDays(3)->toDateString(),
            'tanggal_berakhir' => $sekarang->addDays(3)->toDateString(),
            'jenis' => 'sakit',
            'mode_dl' => null,
            'keterangan' => 'Sakit menunggu persetujuan (seed)',
            'status' => 'pending',
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
            'catatan' => null,
        ]);

        $hasil['pending_dl_ahmad'] = $this->simpanPengajuan([
            'id_pegawai' => $pegawai['pegawai3@esensi.test']->id,
            'id_cuti' => null,
            'tanggal_mulai' => $sekarang->addDays(5)->toDateString(),
            'tanggal_berakhir' => $sekarang->addDays(5)->toDateString(),
            'jenis' => 'dl',
            'mode_dl' => 'full',
            'keterangan' => 'Dinas luar menunggu persetujuan (seed)',
            'status' => 'pending',
            'disetujui_oleh' => null,
            'disetujui_pada' => null,
            'catatan' => null,
        ]);

        $hasil['rejected_cuti_siti'] = $this->simpanPengajuan([
            'id_pegawai' => $pegawai['pegawai2@esensi.test']->id,
            'id_cuti' => $c01?->id,
            'tanggal_mulai' => $sekarang->subDays(10)->toDateString(),
            'tanggal_berakhir' => $sekarang->subDays(10)->toDateString(),
            'jenis' => 'cuti',
            'mode_dl' => null,
            'keterangan' => 'Cuti tahunan ditolak (seed)',
            'status' => 'rejected',
            'disetujui_oleh' => $admin->id,
            'disetujui_pada' => now(),
            'catatan' => 'Ditolak karena kebutuhan operasional (seed).',
        ]);

        return $hasil;
    }

    /**
     * @param  Collection<string, Pegawai>  $pegawai
     * @param  array<string, Pengajuan>  $pengajuan
     * @param  list<CarbonImmutable>  $bulanIni
     * @param  list<CarbonImmutable>  $bulanLalu
     */
    private function seedPresensi(
        Collection $pegawai,
        array $pengajuan,
        JamKerja $jk01,
        JamKerja $jk02,
        JamKerja $jk03,
        CarbonImmutable $sekarang,
        array $bulanIni,
        array $bulanLalu,
        ?CarbonImmutable $akhirPekan,
        ?string $foto,
    ): void {
        $hitung = app(HitungPenggantianKeterlambatanAction::class);
        $koordinat = config('presensi.latitude').','.config('presensi.longitude');

        $budi = $pegawai['pegawai@esensi.test'];
        $siti = $pegawai['pegawai2@esensi.test'];
        $ahmad = $pegawai['pegawai3@esensi.test'];
        $rina = $pegawai['pegawai4@esensi.test'];
        $joko = $pegawai['pegawai5@esensi.test'];

        $shiftHariIni = $this->shiftUntuk($sekarang, $jk01, $jk02, $jk03);

        // Jika seeder dijalankan sebelum jam masuk, biarkan akun utama belum
        // absen agar halaman presensi bisa diuji dari awal.
        if ($sekarang->format('H:i:s') >= $shiftHariIni->awal_masuk) {
            $jamMasukBudi = $this->jamMasuk($sekarang, $shiftHariIni, 15);
            $jamMasukBudi = min($jamMasukBudi, $sekarang->format('H:i:s'));

            $this->simpanPresensi($budi, $sekarang, $shiftHariIni, [
                'status' => 'hadir',
                'jam_masuk' => $jamMasukBudi,
                'koordinat_masuk' => $koordinat,
                'foto_masuk' => $foto,
            ], $hitung);
        }

        if ($sekarang->format('H:i:s') >= $shiftHariIni->awal_masuk) {
            $jamMasukSiti = min(
                $this->jamMasuk($sekarang, $shiftHariIni),
                $sekarang->format('H:i:s'),
            );
            $jamKeluarSiti = $this->jamKeluar($sekarang, $shiftHariIni, 5);
            $jamKeluarSiti = $jamKeluarSiti <= $sekarang->format('H:i:s') ? $jamKeluarSiti : null;

            $this->simpanPresensi($siti, $sekarang, $shiftHariIni, [
                'status' => 'hadir',
                'jam_masuk' => $jamMasukSiti,
                'jam_keluar' => $jamKeluarSiti,
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $jamKeluarSiti === null ? null : $koordinat,
                'foto_masuk' => $foto,
                'foto_keluar' => $jamKeluarSiti === null ? null : $foto,
            ], $hitung);
        }

        $this->simpanPresensi($ahmad, $sekarang, null, [
            'status' => 'cuti',
            'id_pengajuan' => $pengajuan['cuti_ahmad_hari_ini']->id,
        ], $hitung);

        $this->simpanPresensi($rina, $sekarang, null, [
            'status' => 'sakit',
            'id_pengajuan' => $pengajuan['sakit_rina_hari_ini']->id,
        ], $hitung);

        $this->simpanPresensi($joko, $sekarang, $shiftHariIni, [
            'status' => 'hadir',
            'id_pengajuan' => $pengajuan['dl_joko_hari_ini']->id,
            'jam_masuk' => $this->jamMasuk($sekarang, $shiftHariIni, 0, '10:00:00'),
            'koordinat_masuk' => $koordinat,
            'foto_masuk' => $foto,
        ], $hitung);

        if (isset($bulanIni[0])) {
            $d1 = $bulanIni[0];
            $shiftD1 = $this->shiftUntuk($d1, $jk01, $jk02, $jk03);

            $this->simpanPresensi($budi, $d1, $shiftD1, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($d1, $shiftD1, 20),
                'jam_keluar' => $this->jamKeluar($d1, $shiftD1, 20),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
                'foto_masuk' => $foto,
            ], $hitung);

            $this->simpanPresensi($siti, $d1, null, [
                'status' => 'cuti',
                'id_pengajuan' => $pengajuan['cuti_siti_bulan_ini']->id,
            ], $hitung);

            $this->simpanPresensi($ahmad, $d1, $shiftD1, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($d1, $shiftD1, 20),
                'jam_keluar' => $this->jamKeluar($d1, $shiftD1, 10),
                'koordinat_masuk' => $koordinat,
            ], $hitung);

            $this->simpanPresensi($rina, $d1, $shiftD1, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($d1, $shiftD1),
                'koordinat_masuk' => $koordinat,
            ], $hitung);

            $this->simpanPresensi($joko, $d1, null, [
                'status' => 'dl',
                'id_pengajuan' => $pengajuan['dl_joko_bulan_ini']->id,
            ], $hitung);
        }

        if (isset($bulanIni[1])) {
            $d2 = $bulanIni[1];
            $shiftD2 = $this->shiftUntuk($d2, $jk01, $jk02, $jk03);

            $this->simpanPresensi($budi, $d2, $shiftD2, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($d2, $shiftD2, 31),
                'jam_keluar' => $this->jamKeluar($d2, $shiftD2, 31),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
            ], $hitung);

            $this->simpanPresensi($rina, $d2, $shiftD2, [
                'status' => 'hadir',
                'jam_masuk' => null,
                'jam_keluar' => $this->jamKeluar($d2, $shiftD2, 5),
                'koordinat_keluar' => $koordinat,
            ], $hitung);

            $this->simpanPresensi($siti, $d2, $shiftD2, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($d2, $shiftD2),
                'jam_keluar' => $this->jamKeluar($d2, $shiftD2, -30),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
            ], $hitung);
        }

        if (isset($bulanIni[2])) {
            $d3 = $bulanIni[2];
            $shiftD3 = $this->shiftUntuk($d3, $jk01, $jk02, $jk03);

            $this->simpanPresensi($budi, $d3, $shiftD3, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($d3, $shiftD3, 45),
                'jam_keluar' => $this->jamKeluar($d3, $shiftD3, 45),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
            ], $hitung);
        }

        if (isset($bulanLalu[0])) {
            $p1 = $bulanLalu[0];
            $shiftP1 = $this->shiftUntuk($p1, $jk01, $jk02, $jk03);

            $this->simpanPresensi($budi, $p1, $shiftP1, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($p1, $shiftP1, -5),
                'jam_keluar' => $this->jamKeluar($p1, $shiftP1, 5),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
                'foto_masuk' => $foto,
                'foto_keluar' => $foto,
            ], $hitung);

            if (isset($pengajuan['cuti_siti_bulan_lalu'])) {
                $this->simpanPresensi($siti, $p1, null, [
                    'status' => 'cuti',
                    'id_pengajuan' => $pengajuan['cuti_siti_bulan_lalu']->id,
                ], $hitung);
            }

            $this->simpanPresensi($ahmad, $p1, $shiftP1, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($p1, $shiftP1, 20),
                'jam_keluar' => $this->jamKeluar($p1, $shiftP1, 10),
                'koordinat_masuk' => $koordinat,
            ], $hitung);

            $this->simpanPresensi($rina, $p1, $shiftP1, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($p1, $shiftP1, 20),
                'jam_keluar' => $this->jamKeluar($p1, $shiftP1, 22),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
            ], $hitung);

            $this->simpanPresensi($joko, $p1, $shiftP1, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($p1, $shiftP1),
                'jam_keluar' => $this->jamKeluar($p1, $shiftP1, 5),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
            ], $hitung);
        }

        if ($akhirPekan !== null) {
            $this->simpanPresensi($joko, $akhirPekan, $jk03, [
                'status' => 'hadir',
                'jam_masuk' => $this->jamMasuk($akhirPekan, $jk03, 30),
                'jam_keluar' => $this->jamKeluar($akhirPekan, $jk03, 30),
                'koordinat_masuk' => $koordinat,
                'koordinat_keluar' => $koordinat,
            ], $hitung);
        }
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function simpanPresensi(
        Pegawai $pegawai,
        CarbonImmutable $tanggal,
        ?JamKerja $shift,
        array $atribut,
        HitungPenggantianKeterlambatanAction $hitung,
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

        $hasil = $hitung($presensi->refresh(), $shift);

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

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function simpanPengajuan(array $atribut): Pengajuan
    {
        return Pengajuan::updateOrCreate(
            [
                'id_pegawai' => $atribut['id_pegawai'],
                'jenis' => $atribut['jenis'],
                'tanggal_mulai' => $atribut['tanggal_mulai'],
                'tanggal_berakhir' => $atribut['tanggal_berakhir'],
            ],
            $atribut,
        );
    }

    private function shiftUntuk(CarbonImmutable $tanggal, JamKerja $jk01, JamKerja $jk02, JamKerja $jk03): JamKerja
    {
        if ($tanggal->isFriday()) {
            return $jk02;
        }

        if ($tanggal->isWeekend()) {
            return $jk03;
        }

        return $jk01;
    }

    private function jamMasuk(CarbonImmutable $tanggal, JamKerja $shift, int $tambahMenit = 0, ?string $jam = null): string
    {
        return CarbonImmutable::parse($tanggal->toDateString().' '.($jam ?? $shift->jam_masuk))
            ->addMinutes($tambahMenit)
            ->format('H:i:s');
    }

    private function jamKeluar(CarbonImmutable $tanggal, JamKerja $shift, int $tambahMenit = 0): string
    {
        return CarbonImmutable::parse($tanggal->toDateString().' '.$shift->jam_pulang)
            ->addMinutes($tambahMenit)
            ->format('H:i:s');
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function hariKerjaSebelum(CarbonInterface $sebelum, int $jumlah): array
    {
        $hasil = [];
        $tanggal = CarbonImmutable::parse($sebelum)->startOfDay()->subDay();

        while (count($hasil) < $jumlah) {
            if (! $tanggal->isWeekend()) {
                $hasil[] = $tanggal;
            }

            $tanggal = $tanggal->subDay();
        }

        return array_reverse($hasil);
    }

    private function akhirPekanSebelum(CarbonInterface $sebelum): ?CarbonImmutable
    {
        $tanggal = CarbonImmutable::parse($sebelum)->startOfDay()->subDay();

        for ($i = 0; $i < 14; $i++) {
            if ($tanggal->isWeekend()) {
                return $tanggal;
            }

            $tanggal = $tanggal->subDay();
        }

        return null;
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
