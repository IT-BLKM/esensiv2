<?php

use App\Actions\Etl\ImportDataLamaAction;
use App\Services\Etl\ImportReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Lewati test yang butuh skema ETL bila migration #8–#17 belum masuk.
 * Test yang tidak butuh skema (guard command) tetap berjalan.
 */
function lewatiBilaSkemaEtlBelumAda(mixed $test): void
{
    if (! Schema::hasTable('pegawai') || ! Schema::hasTable('saldo_cuti')) {
        $test->markTestSkipped('Skema ETL belum ada — aktif setelah migration #8–#17 merge.');
    }
}

function jalankanEtl(string $path, bool $dryRun = false): ImportReport
{
    return app(ImportDataLamaAction::class)->run($path, null, $dryRun);
}

test('command menolak dump yang berada di dalam direktori repo', function () {
    $this->artisan('presensi:import-lama', ['--path' => base_path('composer.json')])
        ->assertFailed()
        ->expectsOutputToContain('dalam direktori repo');
});

test('command menolak --path kosong dan file yang tidak ada', function () {
    $this->artisan('presensi:import-lama')
        ->assertFailed()
        ->expectsOutputToContain('--path wajib');

    $this->artisan('presensi:import-lama', ['--path' => '/lokasi/tidak/ada.sql'])
        ->assertFailed()
        ->expectsOutputToContain('tidak ditemukan');
});

test('command menolak --since dengan format yang salah', function () {
    $path = tulisDumpLama('-- kosong');

    try {
        $this->artisan('presensi:import-lama', ['--path' => $path, '--since' => '31-12-2026'])
            ->assertFailed()
            ->expectsOutputToContain('YYYY-MM-DD');
    } finally {
        unlink($path);
    }
});

test('dry-run menghitung rencana tanpa menulis satu baris pun', function () {
    lewatiBilaSkemaEtlBelumAda($this);

    $path = tulisDumpLama(sqlDumpLama());

    try {
        $laporan = jalankanEtl($path, dryRun: true);
    } finally {
        unlink($path);
    }

    expect($laporan->dryRun())->toBeTrue()
        ->and($laporan->jumlahTabel('pegawai', true))->toBe(2)
        ->and($laporan->jumlahTabel('user', true))->toBe(2)
        ->and($laporan->jumlahTabel('pengajuan', true))->toBe(8)
        ->and($laporan->jumlahTabel('presensi', true))->toBe(3)
        ->and($laporan->jumlahTabel('saldo_cuti', true))->toBe(4)
        ->and(DB::table('pegawai')->count())->toBe(0)
        ->and(DB::table('user')->count())->toBe(0)
        ->and(DB::table('pengajuan')->count())->toBe(0)
        ->and(DB::table('presensi')->count())->toBe(0);
});

test('eksekusi mengimpor data sesuai rancangan dan membersihkan data kotor', function () {
    lewatiBilaSkemaEtlBelumAda($this);

    $path = tulisDumpLama(sqlDumpLama());

    try {
        $laporan = jalankanEtl($path);
    } finally {
        unlink($path);
    }

    // Master: angka cuti dari keputusan #7 (12/90/30/90), bukan jml_hari legacy 24
    $c01 = DB::table('cuti')->where('kode', 'C01')->first();
    expect(DB::table('departemen')->count())->toBe(1)
        ->and(DB::table('jam_kerja')->count())->toBe(2)
        ->and(DB::table('cuti')->count())->toBe(4)
        ->and($c01->kuota)->toBe(12)
        ->and($c01->maks_hari)->toBe(12)
        ->and($c01->maks_bawa)->toBe(6);

    // User + pegawai: admin lama tidak diimpor; semua pegawai memakai password awal 123456.
    $userA = DB::table('user')->where('email', '199302032022032001@presensi.test')->first();
    $userB = DB::table('user')->where('email', '198009082003122003@presensi.test')->first();

    expect(DB::table('user')->count())->toBe(2)
        ->and(DB::table('user')->where('email', 'admin.lama@presensi.test')->exists())->toBeFalse()
        ->and($userA)->not->toBeNull()
        ->and(Hash::check('123456', $userA->password))->toBeTrue()
        ->and(Hash::check('rahasia-lama-a', $userA->password))->toBeFalse()
        ->and($userB)->not->toBeNull()
        ->and(Hash::check('123456', $userB->password))->toBeTrue()
        ->and(DB::table('pegawai')->count())->toBe(2)
        ->and(DB::table('pegawai')->where('nip', '198009082003122003')->value('foto'))->toBeNull();

    // Jadwal: 5 baris bersih (duplikat Senin + NIP asing dibuang)
    expect(DB::table('konfigurasi_jam_kerja')->count())->toBe(5);

    // Pengajuan: semua baris lolos, CHECK jenis ⇔ id_cuti terpenuhi, pending tetap pending
    expect(DB::table('pengajuan')->count())->toBe(8)
        ->and(DB::table('pengajuan')->where('jenis', 'cuti')->whereNull('id_cuti')->count())->toBe(0)
        ->and(DB::table('pengajuan')->where('jenis', '!=', 'cuti')->whereNotNull('id_cuti')->count())->toBe(0)
        ->and(DB::table('pengajuan')->where('status', 'pending')->count())->toBe(1);

    // Presensi: 10 dari 11 baris dibuang (1 duplikat, 1 yatim, 1 tanpa shift, 1 pending,
    // 1 tanggal 0000, 1 NIP asing, 1 NIP kosong, 1 di luar jendela) → 3 baris bersih
    expect(DB::table('presensi')->count())->toBe(3)
        ->and(DB::table('presensi')->pluck('status')->sort()->values()->all())->toBe(['cuti', 'dl', 'hadir']);

    // Penyelarasan: baris yang tadinya status 'c' menunjuk pengajuan DL → menjadi 'dl'
    $idPengajuanDl = DB::table('pengajuan')->where('keterangan', 'Dinas luar kantor')->value('id');
    expect(DB::table('presensi')->where('status', 'dl')->value('id_pengajuan'))->toBe($idPengajuanDl)
        ->and(DB::table('presensi')->where('status', 'hadir')->value('id_jam_kerja'))->not->toBeNull()
        ->and(DB::table('presensi')->where('status', 'cuti')->value('foto_masuk'))->toBeNull();

    // Alasan pembuangan sesuai runbook §3
    expect($laporan->jumlahBuang('duplikat presensi (nip,tgl)'))->toBe(1)
        ->and($laporan->jumlahBuang('kode pengajuan tidak ditemukan'))->toBe(1)
        ->and($laporan->jumlahBuang('non-hadir tanpa shift'))->toBe(1)
        ->and($laporan->jumlahBuang('pengajuan belum disetujui'))->toBe(1)
        ->and($laporan->jumlahBuang('di luar jendela'))->toBe(1)
        ->and($laporan->jumlahBuang('tanggal tidak valid'))->toBe(1)
        ->and($laporan->jumlahBuang('NIP tidak valid'))->toBe(1)
        ->and($laporan->jumlahBuang('NIP bukan pegawai'))->toBe(2) // 1 presensi + 1 jadwal
        ->and($laporan->jumlahBuang('duplikat jadwal (nip,hari)'))->toBe(1);

    // Lokasi kantor: radius keputusan QA 2,5 km masuk ke laporan sebagai saran .env
    expect(implode("\n", $laporan->barisLaporan()))
        ->toContain('PRESENSI_RADIUS=2500')
        ->toContain('PRESENSI_LAT=-2.91380858459444');
});

test('init saldo_cuti memakai rumus jendela 2 tahun dengan pemakaian hari kerja shift', function () {
    lewatiBilaSkemaEtlBelumAda($this);

    $tahun = now()->year;
    $path = tulisDumpLama(sqlDumpLama());

    try {
        $laporan = jalankanEtl($path);
    } finally {
        unlink($path);
    }

    $saldo = DB::table('saldo_cuti')
        ->join('pegawai', 'pegawai.id', '=', 'saldo_cuti.id_pegawai')
        ->where('pegawai.nip', '199302032022032001')
        ->pluck('saldo_cuti.saldo', 'saldo_cuti.tahun_sumber');

    // Pemakaian C01 tahun lalu = 8 hari kerja shift → min(max(12-8, 0), 6) = 4
    expect($saldo[$tahun - 1])->toBe(4)
        ->and($saldo[$tahun - 2])->toBe(6);

    $saldoB = DB::table('saldo_cuti')
        ->join('pegawai', 'pegawai.id', '=', 'saldo_cuti.id_pegawai')
        ->where('pegawai.nip', '198009082003122003')
        ->pluck('saldo_cuti.saldo', 'saldo_cuti.tahun_sumber');

    expect($saldoB[$tahun - 1])->toBe(6)
        ->and($saldoB[$tahun - 2])->toBe(6)
        ->and(DB::table('saldo_cuti')->count())->toBe(4)
        ->and($laporan->jumlahSaldoHari($tahun - 1))->toBe(10)
        ->and($laporan->jumlahSaldoHari($tahun - 2))->toBe(12);
});

test('run ulang tidak menggandakan baris (idempoten)', function () {
    lewatiBilaSkemaEtlBelumAda($this);

    $path = tulisDumpLama(sqlDumpLama());

    try {
        jalankanEtl($path);
        $kedua = jalankanEtl($path);
    } finally {
        unlink($path);
    }

    expect(DB::table('pegawai')->count())->toBe(2)
        ->and(DB::table('user')->count())->toBe(2)
        ->and(DB::table('konfigurasi_jam_kerja')->count())->toBe(5)
        ->and(DB::table('pengajuan')->count())->toBe(8)
        ->and(DB::table('presensi')->count())->toBe(3)
        ->and(DB::table('saldo_cuti')->count())->toBe(4)
        ->and($kedua->jumlahTabel('presensi', true))->toBe(0)
        ->and($kedua->jumlahTabel('presensi', false))->toBe(3)
        ->and($kedua->jumlahTabel('pengajuan', true))->toBe(0)
        ->and($kedua->jumlahTabel('pengajuan', false))->toBe(8)
        ->and($kedua->jumlahTabel('saldo_cuti', true))->toBe(0);
});
