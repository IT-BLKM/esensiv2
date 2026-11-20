<?php

use App\Models\Departemen;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('stores a departemen with its kode and nama', function () {
    $departemen = Departemen::factory()->create();

    $this->assertModelExists($departemen);
});

test('rejects a departemen with a duplicate kode', function () {
    Departemen::factory()->create(['kode' => 'ADKL']);

    Departemen::factory()->create(['kode' => 'ADKL']);
})->throws(QueryException::class);
