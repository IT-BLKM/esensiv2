<?php

use App\Models\Cuti;
use App\Models\SaldoCuti;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a balance for its source year', function () {
    $saldo = SaldoCuti::factory()->create([
        'tahun_sumber' => 2025,
        'saldo' => 6,
    ]);

    $this->assertModelExists($saldo);
    expect($saldo->tahun_sumber)->toBe(2025)
        ->and($saldo->saldo)->toBe(6);
});

test('allows the same cuti for different source years', function () {
    $cuti = Cuti::factory()->create();
    $first = SaldoCuti::factory()->create([
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2025,
    ]);

    SaldoCuti::factory()->create([
        'id_pegawai' => $first->id_pegawai,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2026,
    ]);

    $this->assertDatabaseCount('saldo_cuti', 2);
});

test('allows the same cuti and year for different pegawai', function () {
    $cuti = Cuti::factory()->create();

    SaldoCuti::factory()->create(['id_cuti' => $cuti->id, 'tahun_sumber' => 2026]);
    SaldoCuti::factory()->create(['id_cuti' => $cuti->id, 'tahun_sumber' => 2026]);

    $this->assertDatabaseCount('saldo_cuti', 2);
});

test('rejects a duplicate pegawai, cuti and source year', function () {
    $cuti = Cuti::factory()->create();
    $first = SaldoCuti::factory()->create([
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2026,
    ]);

    SaldoCuti::factory()->create([
        'id_pegawai' => $first->id_pegawai,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2026,
    ]);
})->throws(QueryException::class);
