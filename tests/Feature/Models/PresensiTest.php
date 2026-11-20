<?php

use App\Models\Presensi;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a hadir record with photo and coordinates', function () {
    $presensi = Presensi::factory()->create([
        'tgl_presensi' => '2026-11-20',
        'status' => 'hadir',
    ]);

    $this->assertModelExists($presensi);
    expect($presensi->status)->toBe('hadir')
        ->and($presensi->foto_masuk)->not->toBeNull()
        ->and($presensi->koordinat_keluar)->not->toBeNull();
});

test('stores an auto-generated cuti row without photo or coordinates', function () {
    $presensi = Presensi::factory()->create([
        'tgl_presensi' => '2026-11-21',
        'id_jam_kerja' => null,
        'id_pengajuan' => 1,
        'jam_masuk' => null,
        'jam_keluar' => null,
        'foto_masuk' => null,
        'foto_keluar' => null,
        'koordinat_masuk' => null,
        'koordinat_keluar' => null,
        'status' => 'cuti',
    ]);

    $this->assertModelExists($presensi);
    expect($presensi->status)->toBe('cuti')
        ->and($presensi->id_pengajuan)->toBe(1)
        ->and($presensi->jam_masuk)->toBeNull()
        ->and($presensi->foto_masuk)->toBeNull()
        ->and($presensi->koordinat_masuk)->toBeNull();
});

test('accepts sakit and dl rows with only the pengajuan trace', function () {
    Presensi::factory()->create([
        'tgl_presensi' => '2026-11-22',
        'id_jam_kerja' => null,
        'id_pengajuan' => 2,
        'jam_masuk' => null,
        'jam_keluar' => null,
        'foto_masuk' => null,
        'foto_keluar' => null,
        'koordinat_masuk' => null,
        'koordinat_keluar' => null,
        'status' => 'sakit',
    ]);
    Presensi::factory()->create([
        'tgl_presensi' => '2026-11-23',
        'id_jam_kerja' => null,
        'id_pengajuan' => 3,
        'jam_masuk' => null,
        'jam_keluar' => null,
        'foto_masuk' => null,
        'foto_keluar' => null,
        'koordinat_masuk' => null,
        'koordinat_keluar' => null,
        'status' => 'dl',
    ]);

    $this->assertDatabaseCount('presensi', 2);
});

test('rejects two records for the same pegawai and date', function () {
    Presensi::factory()->create([
        'id_pegawai' => 1,
        'tgl_presensi' => '2026-11-20',
    ]);

    Presensi::factory()->create([
        'id_pegawai' => 1,
        'tgl_presensi' => '2026-11-20',
    ]);
})->throws(QueryException::class);

test('allows the same date for different pegawai', function () {
    Presensi::factory()->create([
        'id_pegawai' => 1,
        'tgl_presensi' => '2026-11-20',
    ]);
    Presensi::factory()->create([
        'id_pegawai' => 2,
        'tgl_presensi' => '2026-11-20',
    ]);

    $this->assertDatabaseCount('presensi', 2);
});
