<?php

use App\Models\Cuti;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\TukinPeriode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 07:45:00', 'Asia/Jakarta'));
    Storage::fake('local');
    config([
        'presensi.latitude' => '-2.91380858459444',
        'presensi.longitude' => '104.69505610526285',
        'presensi.radius' => 2500,
        'presensi.zona_waktu' => 'Asia/Jakarta',
    ]);
});

function adminPeriodeTukin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function shiftPeriodeTukin(): JamKerja
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

function jadwalkanPeriodeTukin(Pegawai $pegawai, JamKerja $shift): void
{
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => 'Rabu',
    ]);
}

it('lets an admin lock and unlock a tukin period', function () {
    $admin = adminPeriodeTukin();

    $this->actingAs($admin)->post(route('admin.tukin.periode.kunci'), [
        'tahun' => 2026,
        'bulan' => 11,
        'catatan' => 'Payroll November final',
    ])->assertRedirect(route('admin.tukin.index', ['bulan' => 11, 'tahun' => 2026]));

    $periode = TukinPeriode::query()->firstOrFail();
    expect($periode->terkunci)->toBeTrue()
        ->and($periode->catatan)->toBe('Payroll November final');

    $this->actingAs($admin)->post(route('admin.tukin.periode.buka', $periode), [
        'tahun' => 2026,
        'bulan' => 11,
    ])->assertRedirect(route('admin.tukin.index', ['bulan' => 11, 'tahun' => 2026]));

    expect($periode->refresh()->terkunci)->toBeFalse();
});

it('rejects presensi when the current period is locked', function () {
    $admin = adminPeriodeTukin();
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $shift = shiftPeriodeTukin();
    jadwalkanPeriodeTukin($pegawai, $shift);
    TukinPeriode::query()->create([
        'tahun' => 2026,
        'bulan' => 11,
        'terkunci' => true,
        'dikunci_oleh' => $admin->id,
        'dikunci_pada' => now(),
    ]);

    $this->actingAs($user)->postJson(route('presensi.store'), [
        'jenis' => 'masuk',
        'foto' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        'koordinat' => '-2.913900,104.695100',
        'akurasi' => '12',
    ])->assertStatus(409)
        ->assertJsonPath('type', 'periode_terkunci');

    expect(Presensi::query()->count())->toBe(0);
});

it('rejects approval of a pengajuan that touches a locked period', function () {
    $admin = adminPeriodeTukin();
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $cuti = Cuti::factory()->create([
        'kode' => Cuti::KODE_TAHUNAN,
        'nama' => 'Cuti Tahunan',
        'kuota' => 12,
        'maks_hari' => 12,
        'maks_bawa' => 6,
    ]);
    $pengajuan = Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tanggal_mulai' => '2026-11-23',
        'tanggal_berakhir' => '2026-11-24',
        'jenis' => 'cuti',
        'status' => 'pending',
    ]);
    TukinPeriode::query()->create([
        'tahun' => 2026,
        'bulan' => 11,
        'terkunci' => true,
        'dikunci_oleh' => $admin->id,
        'dikunci_pada' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertSessionHasErrors([
            'pengajuan' => 'Tidak bisa disetujui: periode TUKIN pada rentang ini sudah dikunci.',
        ]);

    expect($pengajuan->refresh()->status)->toBe('pending')
        ->and(Presensi::query()->count())->toBe(0);
});
