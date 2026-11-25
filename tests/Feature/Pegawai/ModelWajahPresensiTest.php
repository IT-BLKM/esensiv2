<?php

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Aset model face-api (issue #35). Deteksi wajah berjalan di browser, jadi
 * test ini mengunci dua kontrak yang bisa dicek dari server:
 * 1. model benar-benar ikut terkirim di public/models beserta file bobotnya;
 * 2. halaman presensi menunjuk ke model lokal dan menyediakan tombol muat
 *    ulang saat model gagal dimuat; absen tidak diizinkan tanpa deteksi.
 */
beforeEach(function () {
    $this->withoutVite();
});

it('ships the local face detection model and its weight file', function () {
    $manifestPath = public_path('models/tiny_face_detector_model-weights_manifest.json');

    expect(is_file($manifestPath))->toBeTrue();

    $manifest = json_decode(
        (string) file_get_contents($manifestPath),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest)->toBeArray()->not->toBeEmpty();

    foreach ($manifest as $berkas) {
        expect($berkas['paths'] ?? [])->not->toBeEmpty();

        foreach ($berkas['paths'] as $path) {
            $bobot = public_path('models/'.$path);

            expect(is_file($bobot))->toBeTrue()
                ->and(filesize($bobot))->toBeGreaterThan(0);
        }
    }
});

it('points the presensi page to the local model and requires face detection', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    Pegawai::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('presensi.index'))
        ->assertOk()
        ->assertSee('data-models-url="'.asset('models').'"', false)
        ->assertSee('id="pesan-model"', false)
        ->assertSee('Deteksi wajah wajib aktif')
        ->assertSee('id="tombol-muat-model"', false)
        ->assertSee('Muat ulang model wajah')
        ->assertDontSee('cdn.jsdelivr.net')
        ->assertDontSee('unpkg.com');
});
