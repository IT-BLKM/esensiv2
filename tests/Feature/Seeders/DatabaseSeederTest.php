<?php

use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('seeds a known admin account with a hashed password', function () {
    $this->seed();

    $table = Schema::hasTable('user') ? 'user' : 'users';
    $namaColumn = Schema::hasColumn($table, 'nama') ? 'nama' : 'name';

    $this->assertDatabaseHas($table, [
        'email' => 'admin@esensi.test',
        $namaColumn => 'Administrator',
    ]);

    $admin = DB::table($table)->where('email', 'admin@esensi.test')->first();

    expect($admin)->not->toBeNull()
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

test('office location is configurable through config/presensi.php', function () {
    expect(config('presensi.latitude'))->toBeString()
        ->and(config('presensi.longitude'))->toBeString()
        ->and(config('presensi.radius'))->toBeInt()
        ->and(config('presensi.radius'))->toBeGreaterThan(0);
});

test('seeds the full development dataset when all migrations are available', function () {
    $this->seed();

    if (! Schema::hasTable('pegawai') || ! Schema::hasTable('cuti')) {
        $this->markTestSkipped('Migration tabel turunan belum tersedia di branch ini.');
    }

    $this->assertDatabaseCount('departemen', 5);
    $this->assertDatabaseCount('jam_kerja', 3);
    $this->assertDatabaseCount('pegawai', 50);
    $this->assertDatabaseCount('cuti', 4);
    $this->assertDatabaseCount('user', 52);
    $this->assertDatabaseCount('konfigurasi_jam_kerja', 254);
    $this->assertDatabaseCount('saldo_cuti', 250);
    expect(DB::table('presensi')->count())->toBeGreaterThanOrEqual(3000)
        ->and(DB::table('pengajuan')->count())->toBeGreaterThanOrEqual(500)
        ->and(DB::table('penggantian_keterlambatan')->count())->toBeGreaterThanOrEqual(100);

    // Keputusan #7: 12/90/30/90, maks_bawa 6 hanya untuk C01.
    $this->assertDatabaseHas('cuti', ['kode' => 'C01', 'maks_hari' => 12, 'kuota' => 12, 'maks_bawa' => 6]);
    $this->assertDatabaseHas('cuti', ['kode' => 'C02', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0]);
    $this->assertDatabaseHas('cuti', ['kode' => 'C03', 'maks_hari' => 30, 'kuota' => 30, 'maks_bawa' => 0]);
    $this->assertDatabaseHas('cuti', ['kode' => 'C04', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0]);

    // Akun pegawai utama dan akun tanpa data pegawai untuk uji peringatan.
    $this->assertDatabaseHas('user', ['email' => 'pegawai@esensi.test', 'role' => 'pegawai']);
    $this->assertDatabaseHas('user', ['email' => 'tanpa-pegawai@esensi.test', 'role' => 'pegawai']);
    $this->assertDatabaseHas('pegawai', ['nip' => '199302032022031001', 'nama' => 'Budi Santoso']);
    $this->assertDatabaseHas('pengajuan', ['status' => 'pending', 'jenis' => 'cuti']);
    $this->assertDatabaseHas('pengajuan', ['status' => 'approved', 'jenis' => 'dl', 'mode_dl' => 'setengah']);

    $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->startOfDay();
    $awalBulanLalu = $sekarang->startOfMonth()->subMonth();
    $akhirBulanLalu = $awalBulanLalu->endOfMonth();
    $hariKerjaBulanLalu = [];

    for ($tanggal = $awalBulanLalu; $tanggal->lte($akhirBulanLalu); $tanggal = $tanggal->addDay()) {
        if (! $tanggal->isWeekend()) {
            $hariKerjaBulanLalu[] = $tanggal->toDateString();
        }
    }

    // Bulan kalender sebelumnya penuh: setiap pegawai punya baris di semua hari kerja.
    expect($hariKerjaBulanLalu)->not->toBeEmpty();

    foreach ($hariKerjaBulanLalu as $tanggal) {
        expect(DB::table('presensi')->where('tgl_presensi', $tanggal)->count())->toBe(50);
    }

    $statusBulanLalu = DB::table('presensi')
        ->whereBetween('tgl_presensi', [$awalBulanLalu->toDateString(), $akhirBulanLalu->toDateString()])
        ->pluck('status')
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($statusBulanLalu)->toBe(['cuti', 'dl', 'hadir', 'sakit']);

    $perPegawai = DB::table('presensi')
        ->whereBetween('tgl_presensi', [$awalBulanLalu->toDateString(), $akhirBulanLalu->toDateString()])
        ->get(['id_pegawai', 'status'])
        ->groupBy('id_pegawai');

    expect($perPegawai)->toHaveCount(50);

    foreach ($perPegawai as $baris) {
        $jenis = collect($baris)->pluck('status')->unique()->sort()->values()->all();

        expect($jenis)->toBe(['cuti', 'dl', 'hadir', 'sakit']);
    }

    // Riwayat mencakup semua bulan yang sudah memiliki hari kerja lampau.
    $bulanTersedia = DB::table('presensi')
        ->pluck('tgl_presensi')
        ->map(fn (string $tanggal): string => substr($tanggal, 0, 7))
        ->unique();

    for ($mundur = 0; $mundur < 3; $mundur++) {
        $bulan = $sekarang->startOfMonth()->subMonths($mundur);

        if ($bulan->startOfMonth()->lte($sekarang->subDay())) {
            expect($bulanTersedia)->toContain($bulan->format('Y-m'));
        }
    }
});

test('seeder can be run twice without creating duplicate demo data', function () {
    $this->seed();

    $jumlahPresensi = DB::table('presensi')->count();
    $jumlahPengajuan = DB::table('pengajuan')->count();
    $jumlahPenggantian = DB::table('penggantian_keterlambatan')->count();

    $this->seed();

    $this->assertDatabaseCount('departemen', 5);
    $this->assertDatabaseCount('jam_kerja', 3);
    $this->assertDatabaseCount('pegawai', 50);
    $this->assertDatabaseCount('user', 52);
    $this->assertDatabaseCount('konfigurasi_jam_kerja', 254);
    $this->assertDatabaseCount('saldo_cuti', 250);

    expect(DB::table('presensi')->count())->toBe($jumlahPresensi)
        ->and(DB::table('pengajuan')->count())->toBe($jumlahPengajuan)
        ->and(DB::table('penggantian_keterlambatan')->count())->toBe($jumlahPenggantian);
});

test('seeded pegawai uses the known demo password', function () {
    $this->seed();

    $pegawai = DB::table('user')->where('email', 'pegawai@esensi.test')->first();

    expect($pegawai)->not->toBeNull()
        ->and(Hash::check('password', $pegawai->password))->toBeTrue();
});

test('production seeding only creates master data without demo accounts', function () {
    $this->app['env'] = 'production';

    (new DatabaseSeeder)->run();

    $this->assertDatabaseCount('departemen', 5);
    $this->assertDatabaseCount('cuti', 4);
    $this->assertDatabaseCount('user', 0);
    $this->assertDatabaseCount('pegawai', 0);
    $this->assertDatabaseCount('pengajuan', 0);
});
