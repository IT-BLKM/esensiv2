<?php

namespace App\Actions\Etl;

use App\Services\Etl\DumpReader;
use App\Services\Etl\ImportReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Impor data lama `presensigps` ke skema baru esensiv2.
 *
 * Rincian pemetaan kolom, aturan pembersihan, dan angka verifikasi:
 * docs/etl-runbook.md (keputusan: issue #7, impl: issue #56).
 */
class ImportDataLamaAction
{
    /** Status lama → enum jenis pengajuan baru. */
    private const JENIS_LAMA = ['c' => 'cuti', 's' => 'sakit', 'd' => 'dl'];

    /** Status lama → enum status presensi baru. */
    private const STATUS_PRESENSI_LAMA = ['h' => 'hadir', 'c' => 'cuti', 's' => 'sakit', 'd' => 'dl'];

    /** status_approved lama → enum status pengajuan baru. */
    private const STATUS_PENGAJUAN_LAMA = ['1' => 'approved', '0' => 'pending', '2' => 'rejected'];

    private const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /**
     * Aturan 4 jenis cuti — keputusan issue #7 (bukan jml_hari legacy).
     *
     * @var list<array{kode: string, nama: string, maks_hari: int, kuota: int, maks_bawa: int}>
     */
    private const CUTI = [
        ['kode' => 'C01', 'nama' => 'Cuti Tahunan', 'maks_hari' => 12, 'kuota' => 12, 'maks_bawa' => 6],
        ['kode' => 'C02', 'nama' => 'Cuti Bersalin', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0],
        ['kode' => 'C03', 'nama' => 'Cuti Alasan Penting', 'maks_hari' => 30, 'kuota' => 30, 'maks_bawa' => 0],
        ['kode' => 'C04', 'nama' => 'Cuti Besar', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0],
    ];

    /** Radius keputusan QA (issue #56): 2,5 km — bukan 25.000 m dari data lama. */
    private const RADIUS_BARU = 2500;

    private bool $dryRun = false;

    private ?LoggerInterface $logger = null;

    private string $waktu = '';

    /** @var array<string, int> kode departemen → id */
    private array $deptId = [];

    /** @var array<string, int> kode jam kerja → id */
    private array $jamKerjaId = [];

    /** @var array<string, int> kode cuti → id */
    private array $cutiId = [];

    /** @var array<string, int> nip → id pegawai */
    private array $pegawaiId = [];

    /** @var array<string, int> email → id user */
    private array $userId = [];

    /** @var array<string, int> email user yang sudah ada di database */
    private array $adaUser = [];

    /** @var array<string, true> kunci "nip|hari" jadwal yang sudah ada di database */
    private array $adaJadwal = [];

    /** @var array<string, list<int>> kunci alami pengajuan → daftar id (urut) */
    private array $adaPengajuan = [];

    /** @var array<string, true> kunci "nip|tanggal" presensi yang sudah ada di database */
    private array $adaPresensi = [];

    /** @var array<string, true> kunci "nip|kode cuti|tahun" saldo yang sudah ada di database */
    private array $adaSaldo = [];

    /** @var array<string, int> kode_cuti lama → id pengajuan baru */
    private array $pengajuanIdLama = [];

    /** @var array<string, array<string, true>> nip → daftar hari yang punya shift */
    private array $jadwalBersih = [];

    private int $diselaraskan = 0;

    private int $jamTidakDikenal = 0;

    private int $keteranganTerpotong = 0;

    public function __construct(private readonly DumpReader $dumpReader) {}

    /**
     * Jalankan ETL. Dry-run tidak menulis apa pun ke database maupun log.
     *
     * @param  string  $path  lokasi file dump SQL lama (wajib di luar repo)
     * @param  string|null  $since  batas bawah tanggal presensi (YYYY-MM-DD), default 365 hari terakhir
     *
     * @throws RuntimeException bila dump tidak terbaca, format --since salah, atau migration belum jalan
     */
    public function run(string $path, ?string $since = null, bool $dryRun = false): ImportReport
    {
        $this->resetStatus($dryRun);
        $sejak = $this->tentukanSejak($since);
        $report = new ImportReport($path, $sejak->toDateString(), $dryRun);

        $sumber = $this->dumpReader->read($path);
        $this->muatPeta();

        $this->imporMaster($sumber, $report);
        $pegawai = $this->imporPegawai($sumber, $report);
        $this->imporJadwal($sumber, $pegawai, $report);
        $infoPengajuan = $this->imporPengajuan($sumber, $pegawai, $report);
        $this->imporPresensi($sumber, $pegawai, $infoPengajuan, $sejak, $report);
        $this->initSaldoCuti($sumber, $pegawai, $report);
        $this->catatLokasiKantor($sumber, $report);

        $this->tulisCatatan($report);

        return $report;
    }

    /**
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     */
    private function imporMaster(array $sumber, ImportReport $report): void
    {
        foreach ($sumber['departemen'] ?? [] as $baris) {
            $kode = $this->teks($baris['kode_dept'] ?? null);
            $nama = $this->teks($baris['nama_dept'] ?? null);

            if ($kode === '' || $nama === '') {
                $this->buang($report, 'data master tidak lengkap', 'departemen');

                continue;
            }

            if (isset($this->deptId[$kode])) {
                $report->catatTabel('departemen', false);

                continue;
            }

            $id = $this->dryRun ? 0 : DB::table('departemen')->insertGetId([
                'kode' => $kode,
                'nama' => $nama,
                'created_at' => $this->waktu,
                'updated_at' => $this->waktu,
            ]);
            $this->deptId[$kode] = $id;
            $report->catatTabel('departemen', true);
        }

        foreach ($sumber['jam_kerja'] ?? [] as $baris) {
            $kode = $this->teks($baris['kode_jam_kerja'] ?? null);

            if ($kode === '') {
                $this->buang($report, 'data master tidak lengkap', 'jam_kerja');

                continue;
            }

            if (isset($this->jamKerjaId[$kode])) {
                $report->catatTabel('jam_kerja', false);

                continue;
            }

            $id = $this->dryRun ? 0 : DB::table('jam_kerja')->insertGetId([
                'kode' => $kode,
                'nama' => $this->teks($baris['nama_jam_kerja'] ?? null),
                'awal_masuk' => $this->kolom($baris['awal_jam_masuk'] ?? null),
                'jam_masuk' => $this->kolom($baris['jam_masuk'] ?? null),
                'akhir_masuk' => $this->kolom($baris['akhir_jam_masuk'] ?? null),
                'jam_pulang' => $this->kolom($baris['jam_pulang'] ?? null),
                'created_at' => $this->waktu,
                'updated_at' => $this->waktu,
            ]);
            $this->jamKerjaId[$kode] = $id;
            $report->catatTabel('jam_kerja', true);
        }

        foreach (self::CUTI as $aturan) {
            if (isset($this->cutiId[$aturan['kode']])) {
                $report->catatTabel('cuti', false);

                continue;
            }

            $id = $this->dryRun ? 0 : DB::table('cuti')->insertGetId([
                'kode' => $aturan['kode'],
                'nama' => $aturan['nama'],
                'maks_hari' => $aturan['maks_hari'],
                'kuota' => $aturan['kuota'],
                'maks_bawa' => $aturan['maks_bawa'],
                'created_at' => $this->waktu,
                'updated_at' => $this->waktu,
            ]);
            $this->cutiId[$aturan['kode']] = $id;
            $report->catatTabel('cuti', true);
        }
    }

    /**
     * Impor pegawai + user baru (tanpa password lama; admin lama tidak diimpor).
     *
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     * @return list<array{nip: string, nama: string}> pegawai hasil bersih
     */
    private function imporPegawai(array $sumber, ImportReport $report): array
    {
        $hasil = [];
        $terlihat = [];

        foreach ($sumber['pegawai'] ?? [] as $baris) {
            $nip = $this->teks($baris['nip'] ?? null);

            if (preg_match('/^\d{18}$/', $nip) !== 1) {
                $this->buang($report, 'NIP tidak valid', "pegawai nip={$nip}");

                continue;
            }

            if (isset($terlihat[$nip])) {
                $this->buang($report, 'duplikat pegawai (nip)', "nip={$nip}");

                continue;
            }
            $terlihat[$nip] = true;

            $kodeDept = $this->teks($baris['kode_dept'] ?? null);
            if (! isset($this->deptId[$kodeDept])) {
                $this->buang($report, 'departemen tidak dikenal', "nip={$nip} dept={$kodeDept}");

                continue;
            }

            $nama = $this->teks($baris['nama_lengkap'] ?? null);
            $email = $nip.'@presensi.test';

            /*
             * Keputusan QA 25 Nov 2026: saat go-live semua akun pegawai
             * memakai password awal yang sama. Hash lama tidak diimpor;
             * pegawai diminta menggantinya di luar aplikasi.
             */
            $password = Hash::make((string) config('auth.default_pegawai_password', '123456'));

            // Satu user per pegawai.
            if (isset($this->userId[$email])) {
                $report->catatTabel('user', false);
            } elseif (isset($this->adaUser[$email])) {
                $this->userId[$email] = (int) $this->adaUser[$email];
                $report->catatTabel('user', false);
            } elseif ($this->dryRun) {
                $report->catatTabel('user', true);
            } else {
                $this->userId[$email] = DB::table('user')->insertGetId([
                    'nama' => $nama,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'pegawai',
                    'created_at' => $this->waktu,
                    'updated_at' => $this->waktu,
                ]);
                $report->catatTabel('user', true);
            }

            if (isset($this->pegawaiId[$nip])) {
                $report->catatTabel('pegawai', false);
                $hasil[] = ['nip' => $nip, 'nama' => $nama];

                continue;
            }

            if (! $this->dryRun) {
                $this->pegawaiId[$nip] = DB::table('pegawai')->insertGetId([
                    'user_id' => $this->userId[$email] ?? null,
                    'id_dept' => $this->deptId[$kodeDept],
                    'nip' => $nip,
                    'nama' => $nama,
                    'jabatan' => $this->kolom($baris['jabatan'] ?? null),
                    'foto' => $this->kolom($baris['foto'] ?? null),
                    'no_hp' => $this->kolom($baris['no_hp'] ?? null),
                    'created_at' => $this->waktu,
                    'updated_at' => $this->waktu,
                ]);
            }
            $report->catatTabel('pegawai', true);
            $hasil[] = ['nip' => $nip, 'nama' => $nama];
        }

        return $hasil;
    }

    /**
     * Impor jadwal shift per hari (dedupe kemunculan pertama, NIP asing dibuang).
     *
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     * @param  list<array{nip: string, nama: string}>  $pegawai
     */
    private function imporJadwal(array $sumber, array $pegawai, ImportReport $report): void
    {
        $dipakai = array_flip(array_column($pegawai, 'nip'));
        $terlihat = [];

        foreach ($sumber['konfigurasi_jamkerja'] ?? [] as $baris) {
            $nip = $this->teks($baris['nip'] ?? null);
            $hari = $this->teks($baris['hari'] ?? null);
            $kodeJam = strtoupper($this->teks($baris['kode_jam_kerja'] ?? null));

            if (preg_match('/^\d{18}$/', $nip) !== 1) {
                $this->buang($report, 'NIP tidak valid', "jadwal nip={$nip}");

                continue;
            }

            if (! isset($dipakai[$nip])) {
                $this->buang($report, 'NIP bukan pegawai', "jadwal nip={$nip}");

                continue;
            }

            if (! in_array($hari, self::HARI, true)) {
                $this->buang($report, 'hari tidak dikenal', "jadwal nip={$nip} hari={$hari}");

                continue;
            }

            if (! isset($this->jamKerjaId[$kodeJam])) {
                $this->buang($report, 'kode jam kerja tidak dikenal', "jadwal nip={$nip} kode={$kodeJam}");

                continue;
            }

            $kunci = $nip.'|'.$hari;
            if (isset($terlihat[$kunci])) {
                $this->buang($report, 'duplikat jadwal (nip,hari)', "nip={$nip} hari={$hari}");

                continue;
            }
            $terlihat[$kunci] = true;
            $this->jadwalBersih[$nip][$hari] = true;

            if (isset($this->adaJadwal[$kunci])) {
                $report->catatTabel('konfigurasi_jam_kerja', false);

                continue;
            }

            if (! $this->dryRun) {
                DB::table('konfigurasi_jam_kerja')->insert([
                    'id_pegawai' => $this->pegawaiId[$nip],
                    'id_jam_kerja' => $this->jamKerjaId[$kodeJam],
                    'hari' => $hari,
                    'created_at' => $this->waktu,
                    'updated_at' => $this->waktu,
                ]);
            }
            $report->catatTabel('konfigurasi_jam_kerja', true);
        }
    }

    /**
     * Impor seluruh riwayat pengajuan (tanpa membuang baris pun).
     *
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     * @param  list<array{nip: string, nama: string}>  $pegawai
     * @return array<string, array{jenis: string, status: string}> kode_cuti lama → jenis & status
     */
    private function imporPengajuan(array $sumber, array $pegawai, ImportReport $report): array
    {
        $dipakai = array_flip(array_column($pegawai, 'nip'));
        $antrean = $this->adaPengajuan;
        $info = [];

        foreach ($sumber['pengajuan_cuti'] ?? [] as $baris) {
            $kodeLama = $this->teks($baris['kode_cuti'] ?? null);
            $nip = $this->teks($baris['nip'] ?? null);
            $dari = $this->teks($baris['tgl_cuti_dari'] ?? null);
            $sampai = $this->teks($baris['tgl_cuti_sampai'] ?? null);
            $jenis = self::JENIS_LAMA[$this->teks($baris['status'] ?? null)] ?? null;
            $status = self::STATUS_PENGAJUAN_LAMA[$this->teks($baris['status_approved'] ?? null)] ?? null;

            if (preg_match('/^\d{18}$/', $nip) !== 1 || ! isset($dipakai[$nip])) {
                $this->buang($report, 'NIP bukan pegawai', "pengajuan kode={$kodeLama} nip={$nip}");

                continue;
            }

            if (! $this->tanggalValid($dari) || ! $this->tanggalValid($sampai) || $sampai < $dari) {
                $this->buang($report, 'tanggal tidak valid', "pengajuan kode={$kodeLama}");

                continue;
            }

            if ($jenis === null) {
                $this->buang($report, 'jenis pengajuan tidak dikenal', "pengajuan kode={$kodeLama}");

                continue;
            }

            if ($status === null) {
                $this->buang($report, 'status pengajuan tidak dikenal', "pengajuan kode={$kodeLama}");

                continue;
            }

            $kodeCuti = null;
            if ($jenis === 'cuti') {
                $kodeCuti = $this->teks($baris['kode_cutii'] ?? null);
                if (! isset($this->cutiId[$kodeCuti])) {
                    $this->buang($report, 'jenis cuti tidak dikenal', "pengajuan kode={$kodeLama} cuti={$kodeCuti}");

                    continue;
                }
            }

            $keterangan = $this->teks($baris['keterangan'] ?? null);
            if (mb_strlen($keterangan) > 255) {
                $keterangan = mb_substr($keterangan, 0, 255);
                $this->keteranganTerpotong++;
            }

            $lampiran = $this->kolom($baris['doc_sid'] ?? null);
            $kunci = $this->kunciAlami($nip, $dari, $sampai, $jenis, $keterangan);

            if (isset($antrean[$kunci]) && $antrean[$kunci] !== []) {
                $id = (int) array_shift($antrean[$kunci]);
                $report->catatTabel('pengajuan', false);
            } elseif ($this->dryRun) {
                $id = 0;
                $report->catatTabel('pengajuan', true);
            } else {
                $id = DB::table('pengajuan')->insertGetId([
                    'id_pegawai' => $this->pegawaiId[$nip],
                    'id_cuti' => $kodeCuti !== null ? $this->cutiId[$kodeCuti] : null,
                    'tanggal_mulai' => $dari,
                    'tanggal_berakhir' => $sampai,
                    'jenis' => $jenis,
                    'keterangan' => $keterangan,
                    'lampiran' => $lampiran,
                    'status' => $status,
                    // data lama tidak menyimpan approver — biarkan NULL (jejak audit diisi sejak go-live)
                    'disetujui_oleh' => null,
                    'disetujui_pada' => null,
                    'catatan' => null,
                    'created_at' => $this->waktu,
                    'updated_at' => $this->waktu,
                ]);
                $report->catatTabel('pengajuan', true);
            }

            if ($kodeLama !== '') {
                $this->pengajuanIdLama[$kodeLama] = $id;
                $info[$kodeLama] = ['jenis' => $jenis, 'status' => $status];
            }
        }

        return $info;
    }

    /**
     * Impor presensi 12 bulan terakhir dengan aturan pembersihan runbook §3.
     *
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     * @param  list<array{nip: string, nama: string}>  $pegawai
     * @param  array<string, array{jenis: string, status: string}>  $infoPengajuan
     */
    private function imporPresensi(array $sumber, array $pegawai, array $infoPengajuan, CarbonImmutable $sejak, ImportReport $report): void
    {
        $dipakai = array_flip(array_column($pegawai, 'nip'));
        $sejakTeks = $sejak->toDateString();
        $kandidat = [];

        foreach ($sumber['presensi'] ?? [] as $baris) {
            $tgl = $this->teks($baris['tgl_presensi'] ?? null);

            if (! $this->tanggalValid($tgl)) {
                $this->buang($report, 'tanggal tidak valid', "presensi id={$this->teks($baris['id'] ?? null)} tgl={$tgl}");

                continue;
            }

            if ($tgl < $sejakTeks) {
                $this->buang($report, 'di luar jendela', "presensi nip={$this->teks($baris['nip'] ?? null)} tgl={$tgl}");

                continue;
            }

            $nip = $this->teks($baris['nip'] ?? null);
            if (preg_match('/^\d{18}$/', $nip) !== 1) {
                $this->buang($report, 'NIP tidak valid', "presensi tgl={$tgl} nip={$nip}");

                continue;
            }

            if (! isset($dipakai[$nip])) {
                $this->buang($report, 'NIP bukan pegawai', "presensi nip={$nip} tgl={$tgl}");

                continue;
            }

            $status = self::STATUS_PRESENSI_LAMA[$this->teks($baris['status'] ?? null)] ?? null;
            if ($status === null) {
                $this->buang($report, 'status tidak dikenal', "presensi nip={$nip} tgl={$tgl}");

                continue;
            }

            $kodeJam = strtoupper($this->teks($baris['kode_jam_kerja'] ?? null));
            $idPengajuan = null;
            $diselaraskanBaris = false;

            if ($status === 'hadir') {
                if ($kodeJam !== '' && ! isset($this->jamKerjaId[$kodeJam])) {
                    $this->jamTidakDikenal++;
                }
            } else {
                $kodePengajuan = $this->teks($baris['kode_cuti'] ?? null);
                $info = $infoPengajuan[$kodePengajuan] ?? null;

                if ($info === null) {
                    $this->buang($report, 'kode pengajuan tidak ditemukan', "presensi nip={$nip} tgl={$tgl} kode={$kodePengajuan}");

                    continue;
                }

                if ($info['status'] !== 'approved') {
                    $this->buang($report, 'pengajuan belum disetujui', "presensi nip={$nip} tgl={$tgl} kode={$kodePengajuan}");

                    continue;
                }

                // Pengajuan adalah sumber kebenaran jenis (runbook §3 butir 4)
                if ($info['jenis'] !== $status) {
                    $status = $info['jenis'];
                    $diselaraskanBaris = true;
                }

                if (! $this->punyaShift($nip, $tgl)) {
                    $this->buang($report, 'non-hadir tanpa shift', "presensi nip={$nip} tgl={$tgl}");

                    continue;
                }

                $idPengajuan = $this->pengajuanIdLama[$kodePengajuan] ?? 0;
            }

            $skor = ($this->kolom($baris['jam_in'] ?? null) !== null ? 1 : 0)
                + ($this->kolom($baris['jam_out'] ?? null) !== null ? 1 : 0)
                + ($this->kolom($baris['foto_in'] ?? null) !== null ? 1 : 0);
            $idLama = (int) ($baris['id'] ?? 0);
            $kunci = $nip.'|'.$tgl;

            if (isset($kandidat[$kunci])) {
                $sebelum = $kandidat[$kunci];
                $menang = $skor > $sebelum['skor'] || ($skor === $sebelum['skor'] && $idLama < $sebelum['id']);
                $this->buang(
                    $report,
                    'duplikat presensi (nip,tgl)',
                    "nip={$nip} tgl={$tgl} id=".($menang ? $sebelum['id'] : $idLama)
                );
                if (! $menang) {
                    continue;
                }
            }

            $kandidat[$kunci] = [
                'id' => $idLama,
                'skor' => $skor,
                'nip' => $nip,
                'tgl' => $tgl,
                'jam_masuk' => $this->kolom($baris['jam_in'] ?? null),
                'jam_keluar' => $this->kolom($baris['jam_out'] ?? null),
                'koordinat_masuk' => $this->kolom($baris['lokasi_in'] ?? null),
                'koordinat_keluar' => $this->kolom($baris['lokasi_out'] ?? null),
                'kode_jam' => $kodeJam,
                'status' => $status,
                'id_pengajuan' => $idPengajuan,
                'diselaraskan' => $diselaraskanBaris,
            ];
        }

        foreach ($kandidat as $baris) {
            if ($baris['diselaraskan']) {
                $this->diselaraskan++;
            }

            if (isset($this->adaPresensi[$baris['nip'].'|'.$baris['tgl']])) {
                $report->catatTabel('presensi', false);

                continue;
            }

            if (! $this->dryRun) {
                DB::table('presensi')->insert([
                    'id_pegawai' => $this->pegawaiId[$baris['nip']],
                    'id_jam_kerja' => $this->jamKerjaId[$baris['kode_jam']] ?? null,
                    'id_pengajuan' => $baris['id_pengajuan'],
                    'tgl_presensi' => $baris['tgl'],
                    'jam_masuk' => $baris['jam_masuk'],
                    'jam_keluar' => $baris['jam_keluar'],
                    // Keputusan A: file foto tidak dimigrasi — kolom sengaja NULL
                    'foto_masuk' => null,
                    'foto_keluar' => null,
                    'koordinat_masuk' => $baris['koordinat_masuk'],
                    'koordinat_keluar' => $baris['koordinat_keluar'],
                    'status' => $baris['status'],
                    'created_at' => $this->waktu,
                    'updated_at' => $this->waktu,
                ]);
            }
            $report->catatTabel('presensi', true);
        }
    }

    /**
     * Init saldo_cuti untuk jendela 2 tahun (Y-1 & Y-2) dari pengajuan C01 approved.
     *
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     * @param  list<array{nip: string, nama: string}>  $pegawai
     */
    private function initSaldoCuti(array $sumber, array $pegawai, ImportReport $report): void
    {
        $tahun = CarbonImmutable::now()->year;
        $aturan = null;
        foreach (self::CUTI as $kandidat) {
            if ($kandidat['kode'] === 'C01') {
                $aturan = $kandidat;
                break;
            }
        }
        if ($aturan === null) {
            throw new RuntimeException('Aturan C01 (cuti tahunan) tidak ditemukan di konstanta CUTI.');
        }
        $pemakaian = [];

        foreach ($sumber['pengajuan_cuti'] ?? [] as $baris) {
            $nip = $this->teks($baris['nip'] ?? null);

            if ($this->teks($baris['status_approved'] ?? null) !== '1'
                || $this->teks($baris['kode_cutii'] ?? null) !== $aturan['kode']
                || preg_match('/^\d{18}$/', $nip) !== 1) {
                continue;
            }

            $dari = $this->teks($baris['tgl_cuti_dari'] ?? null);
            $sampai = $this->teks($baris['tgl_cuti_sampai'] ?? null);
            if (! $this->tanggalValid($dari) || ! $this->tanggalValid($sampai) || $sampai < $dari) {
                continue;
            }

            $tahunSumber = (int) substr($dari, 0, 4);
            if ($tahunSumber < $tahun - 2 || $tahunSumber > $tahun) {
                continue;
            }

            $kunci = $nip.'|'.$tahunSumber;
            $pemakaian[$kunci] = ($pemakaian[$kunci] ?? 0) + $this->hariKerjaShift($nip, $dari, $sampai);
        }

        foreach ($pegawai as $pegawaiBaris) {
            $nip = $pegawaiBaris['nip'];

            foreach ([$tahun - 1, $tahun - 2] as $tahunSumber) {
                $terpakai = $pemakaian[$nip.'|'.$tahunSumber] ?? 0;
                $saldo = min(max($aturan['kuota'] - $terpakai, 0), $aturan['maks_bawa']);

                if ($saldo <= 0) {
                    continue;
                }

                $report->catatSaldo($tahunSumber, $saldo);
                $kunci = $nip.'|'.$aturan['kode'].'|'.$tahunSumber;

                if (isset($this->adaSaldo[$kunci])) {
                    $report->catatTabel('saldo_cuti', false);

                    continue;
                }

                if (! $this->dryRun) {
                    DB::table('saldo_cuti')->insert([
                        'id_pegawai' => $this->pegawaiId[$nip],
                        'id_cuti' => $this->cutiId[$aturan['kode']],
                        'tahun_sumber' => $tahunSumber,
                        'saldo' => $saldo,
                        'created_at' => $this->waktu,
                        'updated_at' => $this->waktu,
                    ]);
                }
                $report->catatTabel('saldo_cuti', true);
            }
        }
    }

    /**
     * Catat saran pengaturan lokasi kantor ke .env (keputusan D: radius 2,5 km).
     *
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     */
    private function catatLokasiKantor(array $sumber, ImportReport $report): void
    {
        $baris = $sumber['konfigurasi_lokasi'][0] ?? null;

        if ($baris === null) {
            $report->catatan('konfigurasi_lokasi kosong — isi PRESENSI_LAT/LONG/RADIUS di .env secara manual.');

            return;
        }

        $bagian = array_map('trim', explode(',', $this->teks($baris['lokasi_kantor'] ?? null)));
        $radiusLama = $this->teks($baris['radius'] ?? null);

        $report->catatan(sprintf(
            'Lokasi kantor → .env: PRESENSI_LAT=%s, PRESENSI_LONG=%s, PRESENSI_RADIUS=%d '
            .'(keputusan QA: 2,5 km; data lama: %s m — tidak dipakai apa adanya)',
            $bagian[0] ?? '?',
            $bagian[1] ?? '?',
            self::RADIUS_BARU,
            $radiusLama !== '' ? $radiusLama : '-'
        ));
    }

    /**
     * @param  array<string, list<array<string, string|int|null>>>  $sumber
     */
    private function tulisCatatan(ImportReport $report): void
    {
        if ($this->diselaraskan > 0) {
            $report->catatan("Status presensi diselaraskan ke jenis pengajuan: {$this->diselaraskan} baris.");
        }

        if ($this->jamTidakDikenal > 0) {
            $report->catatan("Baris hadir dengan kode jam kerja tidak dikenal (id_jam_kerja dikosongkan): {$this->jamTidakDikenal} baris.");
        }

        if ($this->keteranganTerpotong > 0) {
            $report->catatan("Keterangan pengajuan dipotong ke 255 karakter: {$this->keteranganTerpotong} baris.");
        }

        $report->catatan('Foto presensi tidak dimigrasi (keputusan A): kolom foto_* = NULL, file tetap di server lama.');
        $report->catatan('Lampiran surat hanya namanya yang disimpan (keputusan B); file tetap di server lama.');
        $report->catatan('User pegawai memakai email {nip}@presensi.test dan password acak — login lewat NIP + reset (issue #21).');
    }

    /**
     * Muat peta baris yang sudah ada di database (read-only — aman untuk dry-run).
     */
    private function muatPeta(): void
    {
        $this->deptId = DB::table('departemen')->pluck('id', 'kode')->all();
        $this->jamKerjaId = DB::table('jam_kerja')->pluck('id', 'kode')->all();
        $this->cutiId = DB::table('cuti')->pluck('id', 'kode')->all();
        $this->pegawaiId = DB::table('pegawai')->pluck('id', 'nip')->all();
        $this->adaUser = DB::table('user')->pluck('id', 'email')->all();

        $this->adaJadwal = DB::table('konfigurasi_jam_kerja')
            ->join('pegawai', 'pegawai.id', '=', 'konfigurasi_jam_kerja.id_pegawai')
            ->get(['pegawai.nip', 'konfigurasi_jam_kerja.hari'])
            ->mapWithKeys(fn ($baris) => [$baris->nip.'|'.$baris->hari => true])
            ->all();

        $this->adaPengajuan = DB::table('pengajuan')
            ->join('pegawai', 'pegawai.id', '=', 'pengajuan.id_pegawai')
            ->get([
                'pegawai.nip',
                'pengajuan.tanggal_mulai',
                'pengajuan.tanggal_berakhir',
                'pengajuan.jenis',
                'pengajuan.keterangan',
                'pengajuan.id',
            ])
            ->groupBy(fn ($baris) => $this->kunciAlami(
                $baris->nip,
                $baris->tanggal_mulai,
                $baris->tanggal_berakhir,
                $baris->jenis,
                (string) $baris->keterangan
            ))
            ->map(fn ($grup) => $grup->pluck('id')->all())
            ->all();

        $this->adaPresensi = DB::table('presensi')
            ->join('pegawai', 'pegawai.id', '=', 'presensi.id_pegawai')
            ->get(['pegawai.nip', 'presensi.tgl_presensi'])
            ->mapWithKeys(fn ($baris) => [$baris->nip.'|'.$baris->tgl_presensi => true])
            ->all();

        $this->adaSaldo = DB::table('saldo_cuti')
            ->join('pegawai', 'pegawai.id', '=', 'saldo_cuti.id_pegawai')
            ->join('cuti', 'cuti.id', '=', 'saldo_cuti.id_cuti')
            ->get(['pegawai.nip', 'cuti.kode', 'saldo_cuti.tahun_sumber'])
            ->mapWithKeys(fn ($baris) => [$baris->nip.'|'.$baris->kode.'|'.$baris->tahun_sumber => true])
            ->all();
    }

    /**
     * Hitung hari kerja (hari dengan shift) dalam satu rentang pengajuan.
     */
    private function hariKerjaShift(string $nip, string $dari, string $sampai): int
    {
        $tanggal = CarbonImmutable::createFromFormat('Y-m-d', $dari)->startOfDay();
        $akhir = CarbonImmutable::createFromFormat('Y-m-d', $sampai)->startOfDay();
        $jumlah = 0;

        while ($tanggal <= $akhir) {
            if ($this->punyaShift($nip, $tanggal->toDateString())) {
                $jumlah++;
            }
            $tanggal = $tanggal->addDay();
        }

        return $jumlah;
    }

    /**
     * Hari dengan shift pegawai — default Senin–Jumat bila tidak ada konfigurasi (aturan bisnis #1).
     */
    private function punyaShift(string $nip, string $tgl): bool
    {
        $tanggal = CarbonImmutable::createFromFormat('Y-m-d', $tgl);
        $hari = self::HARI[$tanggal->dayOfWeekIso - 1];

        if (isset($this->jadwalBersih[$nip])) {
            return isset($this->jadwalBersih[$nip][$hari]);
        }

        return $tanggal->isWeekday();
    }

    private function kunciAlami(string $nip, string $dari, string $sampai, string $jenis, string $keterangan): string
    {
        return implode('|', [$nip, $dari, $sampai, $jenis, $keterangan]);
    }

    private function tanggalValid(string $tgl): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tgl, $cocok) !== 1 || $cocok[1] === '0000') {
            return false;
        }

        return checkdate((int) $cocok[2], (int) $cocok[3], (int) $cocok[1]);
    }

    private function tentukanSejak(?string $since): CarbonImmutable
    {
        if ($since === null) {
            return CarbonImmutable::now()->subDays(365)->startOfDay();
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $since) !== 1 || ! $this->tanggalValid($since)) {
            throw new RuntimeException("--since harus format YYYY-MM-DD yang valid, diterima: {$since}");
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $since)->startOfDay();
    }

    /**
     * Nilai teks dari dump: null → string kosong, sisanya di-trim.
     */
    /**
     * Hash password lama tidak lagi diimpor; semua akun pegawai memakai
     * password awal dari config `auth.default_pegawai_password`.
     */
    private function teks(mixed $nilai): string
    {
        if ($nilai === null) {
            return '';
        }

        return trim((string) $nilai);
    }

    /**
     * Nilai kolom dari dump: null, string 'null', dan string kosong → null.
     */
    private function kolom(mixed $nilai): string|int|null
    {
        if ($nilai === null) {
            return null;
        }

        $teks = trim((string) $nilai);

        if ($teks === '' || strcasecmp($teks, 'null') === 0) {
            return null;
        }

        return $teks;
    }

    private function buang(ImportReport $report, string $alasan, string $konteks): void
    {
        $report->catatBuang($alasan);
        $this->logger?->info("[{$alasan}] {$konteks}");
    }

    private function resetStatus(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
        $this->logger = $dryRun ? null : Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/etl-import.log'),
            'level' => 'info',
        ]);
        $this->waktu = now()->toDateTimeString();
        $this->deptId = [];
        $this->jamKerjaId = [];
        $this->cutiId = [];
        $this->pegawaiId = [];
        $this->userId = [];
        $this->adaUser = [];
        $this->adaJadwal = [];
        $this->adaPengajuan = [];
        $this->adaPresensi = [];
        $this->adaSaldo = [];
        $this->pengajuanIdLama = [];
        $this->jadwalBersih = [];
        $this->diselaraskan = 0;
        $this->jamTidakDikenal = 0;
        $this->keteranganTerpotong = 0;
    }
}
