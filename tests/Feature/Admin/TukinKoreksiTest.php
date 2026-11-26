<?php

use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\TukinKoreksi;
use App\Models\TukinPeriode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function pegawaiKoreksiTukin(): array
{
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $shift = JamKerja::factory()->create([
        'kode' => 'JK01',
        'nama' => 'Shift Pagi',
        'jam_masuk' => '07:30:00',
        'jam_pulang' => '16:00:00',
    ]);
    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => '2026-11-02',
        'jam_masuk' => '07:40:00',
        'jam_keluar' => '16:00:00',
        'status' => 'hadir',
    ]);

    return [$user, $pegawai, $shift];
}

it('adds an audited manual correction to the tukin total', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    [, $pegawai] = pegawaiKoreksiTukin();

    $this->actingAs($admin)->post(route('admin.tukin.koreksi.store'), [
        'id_pegawai' => $pegawai->id,
        'tahun' => 2026,
        'bulan' => 11,
        'persen' => 1.5,
        'alasan' => 'Koreksi data presensi',
    ])->assertRedirect(route('admin.tukin.index', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]));

    $koreksi = TukinKoreksi::query()->firstOrFail();
    expect((float) $koreksi->persen)->toBe(1.5)
        ->and($koreksi->dibuat_oleh)->toBe($admin->id);

    $this->actingAs($admin)->get(route('admin.tukin.index', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Koreksi data presensi')
        ->assertSee('2,50%');
});

it('rejects a zero correction', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    [, $pegawai] = pegawaiKoreksiTukin();

    $this->actingAs($admin)->post(route('admin.tukin.koreksi.store'), [
        'id_pegawai' => $pegawai->id,
        'tahun' => 2026,
        'bulan' => 11,
        'persen' => 0,
        'alasan' => 'Tidak ada perubahan',
    ])->assertSessionHasErrors([
        'persen' => 'Persentase koreksi tidak boleh nol.',
    ]);
});

it('allows corrections even when the period is locked', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    [, $pegawai] = pegawaiKoreksiTukin();
    TukinPeriode::query()->create([
        'tahun' => 2026,
        'bulan' => 11,
        'terkunci' => true,
        'dikunci_oleh' => $admin->id,
        'dikunci_pada' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.tukin.koreksi.store'), [
        'id_pegawai' => $pegawai->id,
        'tahun' => 2026,
        'bulan' => 11,
        'persen' => -0.5,
        'alasan' => 'Koreksi setelah kunci',
    ])->assertRedirect();

    expect(TukinKoreksi::query()->count())->toBe(1);
});
