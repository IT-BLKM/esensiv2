<?php

use App\Models\Pegawai;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('stores a pegawai with its nip and user_id', function () {
    $pegawai = Pegawai::factory()->create(['nip' => '198711022010121005']);

    $this->assertModelExists($pegawai);
    expect($pegawai->nip)->toBe('198711022010121005');
});

test('does not store a password on pegawai', function () {
    expect(Schema::hasColumn('pegawai', 'password'))->toBeFalse();
});

test('rejects a pegawai with a duplicate nip', function () {
    Pegawai::factory()->create(['nip' => '198711022010121005']);

    Pegawai::factory()->create(['nip' => '198711022010121005']);
})->throws(QueryException::class);

test('rejects a pegawai with a duplicate user_id', function () {
    $existing = Pegawai::factory()->create();

    Pegawai::factory()->create(['user_id' => $existing->user_id]);
})->throws(QueryException::class);
