<?php

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
    $this->assertDatabaseCount('jam_kerja', 2);
    $this->assertDatabaseCount('pegawai', 5);
    $this->assertDatabaseCount('cuti', 4);
    $this->assertDatabaseCount('konfigurasi_jam_kerja', 25);
    $this->assertDatabaseCount('saldo_cuti', 25);

    // Keputusan #7: 12/90/30/90, maks_bawa 6 hanya untuk C01.
    $this->assertDatabaseHas('cuti', ['kode' => 'C01', 'maks_hari' => 12, 'kuota' => 12, 'maks_bawa' => 6]);
    $this->assertDatabaseHas('cuti', ['kode' => 'C02', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0]);
    $this->assertDatabaseHas('cuti', ['kode' => 'C03', 'maks_hari' => 30, 'kuota' => 30, 'maks_bawa' => 0]);
    $this->assertDatabaseHas('cuti', ['kode' => 'C04', 'maks_hari' => 90, 'kuota' => 90, 'maks_bawa' => 0]);
});
