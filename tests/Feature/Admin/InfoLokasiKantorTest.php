<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

/**
 * Nomor issue yang diuji di sini: #30 — halaman info lokasi & radius kantor.
 *
 * Nilai selalu dibaca dari config(), bukan ditulis literal, supaya test tetap
 * benar walau .env tiap developer berbeda.
 */
function adminInfoLokasi(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('shows the office location and radius read from config', function () {
    $radius = (int) config('presensi.radius');

    $this->actingAs(adminInfoLokasi())->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertSee('Lokasi & radius absen')
        ->assertSee('Lintang (latitude)')
        ->assertSee((string) config('presensi.latitude'))
        ->assertSee('Bujur (longitude)')
        ->assertSee((string) config('presensi.longitude'))
        ->assertSee('Radius absen')
        ->assertSee(number_format($radius, 0, ',', '.').' m')
        ->assertSee('Buka di peta');
});

it('links the coordinates to a map without hardcoding a url literal', function () {
    $titik = urlencode(config('presensi.latitude').','.config('presensi.longitude'));

    $this->actingAs(adminInfoLokasi())->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertSee('https://www.google.com/maps/search/?api=1&amp;query='.$titik, false)
        ->assertSee('rel="noopener noreferrer"', false);
});

it('keeps the location card read-only with no field to edit', function () {
    // Layout admin punya form logout, jadi yang dicek hanya medan lokasi.
    $this->actingAs(adminInfoLokasi())->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertDontSee('name="latitude"', false)
        ->assertDontSee('name="longitude"', false)
        ->assertDontSee('name="radius"', false)
        ->assertDontSee('name="PRESENSI_', false);
});

it('has no route that could change the location', function () {
    $namaRoute = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter(fn (?string $nama) => $nama !== null && str_contains($nama, 'lokasi'));

    expect($namaRoute)->toBeEmpty();
});

it('warns when the location config is incomplete', function () {
    config()->set('presensi.radius', 0);

    $this->actingAs(adminInfoLokasi())->get(route('admin.konfigurasi.index'))
        ->assertOk()
        ->assertSee('Konfigurasi belum lengkap')
        ->assertDontSee('Buka di peta');
});

it('stays usable when the PRESENSI variables are missing from the environment', function () {
    $kunci = ['PRESENSI_LAT', 'PRESENSI_LONG', 'PRESENSI_RADIUS'];
    $cadangan = [];

    foreach ($kunci as $nama) {
        $cadangan[$nama] = env($nama);
        putenv($nama);
        unset($_ENV[$nama], $_SERVER[$nama]);
    }

    try {
        $presensi = require base_path('config/presensi.php');

        expect($presensi['latitude'])->toBeNumeric()
            ->and((float) $presensi['latitude'])->toBeBetween(-90.0, 90.0)
            ->and($presensi['longitude'])->toBeNumeric()
            ->and((float) $presensi['longitude'])->toBeBetween(-180.0, 180.0)
            ->and($presensi['radius'])->toBeInt()
            ->and($presensi['radius'])->toBeGreaterThan(0);
    } finally {
        foreach ($cadangan as $nama => $nilai) {
            if ($nilai === null) {
                continue;
            }

            putenv("{$nama}={$nilai}");
            $_ENV[$nama] = $nilai;
            $_SERVER[$nama] = $nilai;
        }
    }
});
