<?php

use App\Models\JamKerja;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a jam kerja with its kode and jam', function () {
    $jamKerja = JamKerja::factory()->create();

    $this->assertModelExists($jamKerja);
    expect($jamKerja->awal_masuk)->toBe('07:30:00')
        ->and($jamKerja->jam_masuk)->toBe('08:00:00')
        ->and($jamKerja->akhir_masuk)->toBe('08:30:00')
        ->and($jamKerja->jam_pulang)->toBe('17:00:00');
});

test('rejects a jam kerja with a duplicate kode', function () {
    JamKerja::factory()->create(['kode' => 'SHFT']);

    JamKerja::factory()->create(['kode' => 'SHFT']);
})->throws(QueryException::class);
