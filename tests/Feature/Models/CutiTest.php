<?php

use App\Models\Cuti;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('stores a cuti rule using maks_bawa and not tabungan_cuti', function () {
    $cuti = Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);

    $this->assertModelExists($cuti);
    expect(Schema::hasColumn('cuti', 'maks_bawa'))->toBeTrue()
        ->and(Schema::hasColumn('cuti', 'tabungan_cuti'))->toBeFalse();
});

test('accepts null kuota and maks_hari for cuti alasan penting', function () {
    $cuti = Cuti::factory()->create([
        'kode' => 'C03',
        'nama' => 'Cuti Alasan Penting',
        'maks_hari' => null,
        'kuota' => null,
        'maks_bawa' => 0,
    ]);

    $this->assertModelExists($cuti);
    expect($cuti->kuota)->toBeNull()
        ->and($cuti->maks_hari)->toBeNull()
        ->and($cuti->maks_bawa)->toBe(0);
});

test('rejects a duplicate kode', function () {
    Cuti::factory()->create(['kode' => 'C01']);

    Cuti::factory()->create(['kode' => 'C01']);
})->throws(QueryException::class);
