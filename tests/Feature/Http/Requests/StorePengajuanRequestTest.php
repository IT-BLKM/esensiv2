<?php

use App\Http\Requests\StorePengajuanRequest;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Route::post('/pengajuan', function (StorePengajuanRequest $request) {
        return response()->json($request->validated(), 201);
    });

    $this->payload = fn (array $overrides = []) => array_merge([
        'id_pegawai' => 1,
        'id_cuti' => 1,
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-22',
        'jenis' => 'cuti',
        'keterangan' => 'Cuti tahunan',
        'status' => 'pending',
    ], $overrides);
});

test('accepts cuti with id_cuti', function () {
    $this->postJson('/pengajuan', ($this->payload)())
        ->assertCreated();
});

test('rejects cuti without id_cuti', function () {
    $this->postJson('/pengajuan', ($this->payload)([
        'jenis' => 'cuti',
        'id_cuti' => null,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('rejects sakit with id_cuti', function () {
    $this->postJson('/pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => 1,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('rejects dl with id_cuti', function () {
    $this->postJson('/pengajuan', ($this->payload)([
        'jenis' => 'dl',
        'id_cuti' => 1,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('accepts sakit without id_cuti', function () {
    $this->postJson('/pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => null,
        'keterangan' => 'Surat dokter',
    ]))
        ->assertCreated();
});

test('rejects a tanggal_berakhir before tanggal_mulai', function () {
    $this->postJson('/pengajuan', ($this->payload)([
        'tanggal_berakhir' => '2026-11-19',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tanggal_berakhir');
});
