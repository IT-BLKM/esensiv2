<?php

use App\Models\KonfigurasiJamKerja;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a configuration for Minggu', function () {
    $konfigurasi = KonfigurasiJamKerja::factory()->create(['hari' => 'Minggu']);

    $this->assertModelExists($konfigurasi);
    expect($konfigurasi->hari)->toBe('Minggu');
});

test('accepts the same hari for different pegawai', function () {
    KonfigurasiJamKerja::factory()->create(['id_pegawai' => 1, 'hari' => 'Senin']);
    KonfigurasiJamKerja::factory()->create(['id_pegawai' => 2, 'hari' => 'Senin']);

    $this->assertDatabaseCount('konfigurasi_jam_kerja', 2);
});

test('rejects two shifts for the same pegawai and hari', function () {
    KonfigurasiJamKerja::factory()->create(['id_pegawai' => 1, 'hari' => 'Senin']);

    KonfigurasiJamKerja::factory()->create(['id_pegawai' => 1, 'hari' => 'Senin']);
})->throws(QueryException::class);
