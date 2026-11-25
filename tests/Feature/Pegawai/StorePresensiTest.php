<?php

use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
 * StorePresensiAction (issue #32) — server tidak mempercayai klien.
 * Empat skenario wajib QA: dalam radius, luar radius, dobel klik, tanpa shift.
 * Semua diuji lewat endpoint POST /presensi supaya FormRequest + Action +
 * response JSON ikut terbukti.
 */
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 07:45:00', 'Asia/Jakarta'));
    Storage::fake('local');
    config([
        'presensi.latitude' => '-2.91380858459444',
        'presensi.longitude' => '104.69505610526285',
        'presensi.radius' => 2500,
        'presensi.zona_waktu' => 'Asia/Jakarta',
    ]);
});

function koordinatDalamPresensi(): string
{
    return '-2.913900,104.695100';
}

function koordinatLuarPresensi(): string
{
    return '-2.980000,104.750000';
}

function fotoPresensiUji(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
}

/**
 * @return array{User, Pegawai}
 */
function akunStorePresensi(): array
{
    $user = User::factory()->create(['role' => 'pegawai']);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

function shiftStorePresensi(): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => 'JK01',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => '16:00:00',
    ]);
}

function jadwalkanStorePresensi(Pegawai $pegawai, JamKerja $shift, string $hari = 'Rabu'): void
{
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => $hari,
    ]);
}

/**
 * @return array<string, string>
 */
function payloadStorePresensi(
    string $jenis = 'masuk',
    ?string $koordinat = null,
    ?string $foto = null,
): array {
    return [
        'jenis' => $jenis,
        'foto' => $foto ?? fotoPresensiUji(),
        'koordinat' => $koordinat ?? koordinatDalamPresensi(),
        'akurasi' => '12',
    ];
}

it('stores the masuk row and a private photo when inside the radius', function () {
    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi());

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('type', 'masuk')
        ->assertJsonPath('data.jam_masuk', '07:45');

    $presensi = Presensi::query()->sole();

    expect($presensi->id_pegawai)->toBe($pegawai->id)
        ->and($presensi->tgl_presensi)->toBe('2026-11-25')
        ->and($presensi->jam_masuk)->toBe('07:45:00')
        ->and($presensi->jam_keluar)->toBeNull()
        ->and($presensi->status)->toBe('hadir')
        ->and($presensi->id_jam_kerja)->not->toBeNull()
        ->and($presensi->koordinat_masuk)->toBe(koordinatDalamPresensi())
        ->and($presensi->foto_masuk)->toStartWith('presensi/');

    Storage::disk('local')->assertExists($presensi->foto_masuk);
});

it('rejects a location outside the radius without creating a row or file', function () {
    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi());

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi(koordinat: koordinatLuarPresensi()))
        ->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('type', 'radius');

    expect(Presensi::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('treats a double click on masuk as already checked in', function () {
    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi());

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertCreated();

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertStatus(409)
        ->assertJsonPath('type', 'sudah_masuk');

    expect(Presensi::query()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toHaveCount(1);
});

it('rejects masuk without a configured shift using a jadwal error', function () {
    [$user] = akunStorePresensi();

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('type', 'jadwal');

    expect(Presensi::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('records keluar after masuk in the same row with a private photo', function () {
    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi());

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertCreated();

    $this->travelTo(CarbonImmutable::parse('2026-11-25 16:10:00', 'Asia/Jakarta'));

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi('keluar'))
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('type', 'keluar')
        ->assertJsonPath('data.jam_keluar', '16:10');

    $presensi = Presensi::query()->sole();

    expect($presensi->jam_masuk)->toBe('07:45:00')
        ->and($presensi->jam_keluar)->toBe('16:10:00')
        ->and($presensi->foto_keluar)->toStartWith('presensi/');

    Storage::disk('local')->assertExists($presensi->foto_masuk);
    Storage::disk('local')->assertExists($presensi->foto_keluar);
});

it('rejects keluar before masuk and a second keluar', function () {
    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi());

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi('keluar'))
        ->assertStatus(409)
        ->assertJsonPath('type', 'belum_masuk');

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi('masuk'))
        ->assertCreated();

    $this->travelTo(CarbonImmutable::parse('2026-11-25 16:10:00', 'Asia/Jakarta'));

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi('keluar'))
        ->assertOk();

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi('keluar'))
        ->assertStatus(409)
        ->assertJsonPath('type', 'sudah_keluar');

    expect(Presensi::query()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles())->toHaveCount(2);
});

it('rejects an invalid photo payload without storing anything', function () {
    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi());

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi(foto: 'data:image/png;base64,%%%%'))
        ->assertStatus(422)
        ->assertJsonPath('type', 'foto');

    expect(Presensi::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('returns structured validation errors for a malformed request', function () {
    [$user] = akunStorePresensi();

    $this->actingAs($user)
        ->postJson(route('presensi.store'), [])
        ->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('type', 'validasi')
        ->assertJsonValidationErrors(['jenis', 'foto', 'koordinat']);
});

it('refuses to overwrite an approved leave row', function () {
    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi());

    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => null,
        'tgl_presensi' => '2026-11-25',
        'jam_masuk' => null,
        'jam_keluar' => null,
        'foto_masuk' => null,
        'foto_keluar' => null,
        'koordinat_masuk' => null,
        'koordinat_keluar' => null,
        'status' => 'cuti',
    ]);

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertStatus(409)
        ->assertJsonPath('type', 'status');

    expect(Presensi::query()->sole()->status)->toBe('cuti')
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('uses the office timezone to decide the attendance date', function () {
    // 18:30 UTC = 01:30 WIB keesokan harinya (Kamis).
    $this->travelTo(CarbonImmutable::parse('2026-11-25 18:30:00', 'UTC'));

    [$user, $pegawai] = akunStorePresensi();
    jadwalkanStorePresensi($pegawai, shiftStorePresensi(), 'Kamis');

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertCreated();

    expect(Presensi::query()->sole()->tgl_presensi)->toBe('2026-11-26');
});

it('rejects attendance when the account has no pegawai data', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)
        ->postJson(route('presensi.store'), payloadStorePresensi())
        ->assertStatus(422)
        ->assertJsonPath('type', 'pegawai');
});
