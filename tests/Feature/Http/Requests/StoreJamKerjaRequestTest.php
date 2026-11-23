<?php

use App\Http\Requests\StoreJamKerjaRequest;
use App\Models\JamKerja;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Route::post('/jam-kerja', function (StoreJamKerjaRequest $request) {
        return response()->json(JamKerja::create($request->validated()), 201);
    });
});

test('accepts a valid jam kerja', function () {
    $response = $this->postJson('/jam-kerja', [
        'kode' => 'SHFT',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:30',
        'jam_masuk' => '08:00',
        'akhir_masuk' => '08:30',
        'jam_pulang' => '17:00',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('jam_kerja', ['kode' => 'SHFT', 'nama' => 'Shift Pagi']);
});

test('rejects a jam kerja with a duplicate kode', function () {
    JamKerja::factory()->create(['kode' => 'SHFT']);

    $response = $this->postJson('/jam-kerja', [
        'kode' => 'SHFT',
        'nama' => 'Shift Siang',
        'awal_masuk' => '12:30',
        'jam_masuk' => '13:00',
        'akhir_masuk' => '13:30',
        'jam_pulang' => '21:00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('kode');
});

test('rejects times that are out of order', function () {
    $response = $this->postJson('/jam-kerja', [
        'kode' => 'SHFT',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:30',
        'jam_masuk' => '09:00',
        'akhir_masuk' => '08:30',
        'jam_pulang' => '17:00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('akhir_masuk');
    $this->assertDatabaseEmpty('jam_kerja');
});
