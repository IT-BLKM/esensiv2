<?php

use App\Models\Pengajuan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a pengajuan with its approval audit columns', function () {
    $pengajuan = Pengajuan::factory()->create([
        'status' => 'approved',
        'disetujui_oleh' => 9,
        'disetujui_pada' => now(),
        'catatan' => 'Disetujui admin',
    ]);

    $this->assertModelExists($pengajuan);
    expect($pengajuan->status)->toBe('approved')
        ->and($pengajuan->disetujui_oleh)->toBe(9)
        ->and($pengajuan->catatan)->toBe('Disetujui admin');
});

test('database rejects cuti without id_cuti', function () {
    Pengajuan::factory()->create(['jenis' => 'cuti', 'id_cuti' => null]);
})->throws(QueryException::class);

test('database rejects sakit with id_cuti', function () {
    Pengajuan::factory()->create(['jenis' => 'sakit', 'id_cuti' => 1]);
})->throws(QueryException::class);

test('database rejects dl with id_cuti', function () {
    Pengajuan::factory()->create(['jenis' => 'dl', 'id_cuti' => 1]);
})->throws(QueryException::class);

test('allows sakit and dl without id_cuti', function () {
    Pengajuan::factory()->create(['jenis' => 'sakit', 'id_cuti' => null]);
    Pengajuan::factory()->create(['jenis' => 'dl', 'id_cuti' => null]);

    $this->assertDatabaseCount('pengajuan', 2);
});
