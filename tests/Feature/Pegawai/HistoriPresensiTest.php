<?php

use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Histori presensi pegawai (issue #33): filter bulan/tahun, daftar, status,
 * dan keterlambatan per hari. Filter diuji juga dengan payload berbahaya
 * untuk memastikan nilainya tidak pernah masuk ke SQL.
 */
beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

/**
 * @return array{User, Pegawai}
 */
function akunHistoriPresensi(): array
{
    $user = User::factory()->create(['role' => 'pegawai']);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

function shiftHistoriPresensi(string $kode = 'JK01'): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => $kode,
        'nama' => 'Shift '.$kode,
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => '16:00:00',
    ]);
}

function buatHistoriPresensi(
    Pegawai $pegawai,
    string $tanggal,
    string $status = 'hadir',
    ?JamKerja $shift = null,
    ?string $jamMasuk = '07:15:00',
    ?string $jamKeluar = '16:00:00',
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $status === 'hadir' ? $shift?->id : null,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $status === 'hadir' ? $jamMasuk : null,
        'jam_keluar' => $status === 'hadir' ? $jamKeluar : null,
        'status' => $status,
    ]);
}

it('shows the current month history ordered from newest to oldest', function () {
    [$user, $pegawai] = akunHistoriPresensi();
    $shift = shiftHistoriPresensi();

    buatHistoriPresensi($pegawai, '2026-11-02', shift: $shift);
    buatHistoriPresensi($pegawai, '2026-11-20', shift: $shift);
    buatHistoriPresensi($pegawai, '2026-10-31', shift: $shift);

    $this->actingAs($user)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('Histori November 2026')
        ->assertSee('2 baris')
        ->assertSeeInOrder(['20 Nov 2026', '02 Nov 2026'])
        ->assertDontSee('31 Okt 2026');
});

it('filters the history by month and year from the query string', function () {
    [$user, $pegawai] = akunHistoriPresensi();
    $shift = shiftHistoriPresensi();

    buatHistoriPresensi($pegawai, '2026-11-20', shift: $shift);
    buatHistoriPresensi($pegawai, '2026-10-05', shift: $shift);
    buatHistoriPresensi($pegawai, '2025-10-11', shift: $shift);

    $this->actingAs($user)->get(route('histori.index', ['bulan' => 10, 'tahun' => 2026]))
        ->assertOk()
        ->assertSee('Histori Oktober 2026')
        ->assertSee('1 baris')
        ->assertSee('05 Okt 2026')
        ->assertDontSee('20 Nov 2026')
        ->assertDontSee('11 Okt 2025');
});

it('shows status and late minutes per day from the shift schedule', function () {
    [$user, $pegawai] = akunHistoriPresensi();
    $shift = shiftHistoriPresensi();

    buatHistoriPresensi($pegawai, '2026-11-02', shift: $shift, jamMasuk: '07:45:00');
    buatHistoriPresensi($pegawai, '2026-11-03', shift: $shift, jamMasuk: '07:15:00');
    buatHistoriPresensi($pegawai, '2026-11-04', status: 'cuti');
    buatHistoriPresensi($pegawai, '2026-11-05', status: 'sakit');
    buatHistoriPresensi($pegawai, '2026-11-06', status: 'dl');

    $this->actingAs($user)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('Terlambat 15 menit')
        ->assertSee('Tepat waktu')
        ->assertSee('Hadir')
        ->assertSee('Cuti')
        ->assertSee('Sakit')
        ->assertSee('Dinas Luar');
});

it('never shows another pegawai history', function () {
    [$userA, $pegawaiA] = akunHistoriPresensi();
    [, $pegawaiB] = akunHistoriPresensi();
    $shiftA = shiftHistoriPresensi();
    $shiftB = shiftHistoriPresensi('JK02');

    $shiftB->update(['nama' => 'Shift Rahasia B']);

    buatHistoriPresensi($pegawaiA, '2026-11-06', shift: $shiftA);
    buatHistoriPresensi($pegawaiB, '2026-11-05', shift: $shiftB);

    $this->actingAs($userA)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('06 Nov 2026')
        ->assertDontSee('05 Nov 2026')
        ->assertDontSee('Shift Rahasia B');
});

it('rejects malformed filter values before they reach the query', function (string $field, string $nilai) {
    [$user] = akunHistoriPresensi();

    $this->actingAs($user)
        ->getJson(route('histori.index', [$field => $nilai]))
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'bulan dengan kutipan SQL' => ['bulan', "11' OR 1=1 --"],
    'tahun dengan OR' => ['tahun', '2026 OR 1=1'],
    'bulan di luar rentang' => ['bulan', '13'],
]);

it('shows the empty state when the selected month has no records', function () {
    [$user] = akunHistoriPresensi();

    $this->actingAs($user)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('0 baris')
        ->assertSee('Belum ada presensi pada November 2026.');
});

it('offers years from the earliest record up to the current year', function () {
    [$user, $pegawai] = akunHistoriPresensi();
    $shift = shiftHistoriPresensi();

    buatHistoriPresensi($pegawai, '2025-03-03', shift: $shift);

    $this->actingAs($user)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('<option value="2026"', false)
        ->assertSee('<option value="2025"', false);
});

it('uses the office timezone for the default period', function () {
    // 18:00 UTC = 01:00 WIB tanggal 1 Desember.
    $this->travelTo(CarbonImmutable::parse('2026-11-30 18:00:00', 'UTC'));

    [$user] = akunHistoriPresensi();

    $this->actingAs($user)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('Histori Desember 2026');
});

it('warns when the account is not linked to pegawai data', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('Data pegawai belum tersedia')
        ->assertDontSee('Filter periode');
});

it('sends a guest to the login page', function () {
    $this->get(route('histori.index'))->assertRedirect(route('login'));
});

it('shows replacement status for late rows', function () {
    [$user, $pegawai] = akunHistoriPresensi();
    $shift = shiftHistoriPresensi();

    buatHistoriPresensi($pegawai, '2026-11-02', shift: $shift, jamMasuk: '07:50:00', jamKeluar: '16:22:00');
    buatHistoriPresensi($pegawai, '2026-11-03', shift: $shift, jamMasuk: '07:50:00', jamKeluar: '16:26:00');
    buatHistoriPresensi($pegawai, '2026-11-04', shift: $shift, jamMasuk: '08:01:00', jamKeluar: '16:31:00');

    $this->actingAs($user)->get(route('histori.index'))
        ->assertOk()
        ->assertSee('— diganti')
        ->assertSee('— belum diganti')
        ->assertSee('— tidak bisa diganti')
        ->assertSee('16:20–16:25');
});
