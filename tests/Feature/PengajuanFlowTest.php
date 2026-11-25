<?php

use App\Models\Cuti;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\SaldoCuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('runs the full pengajuan flow from submit through approve and cancel', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);

    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $shift = JamKerja::factory()->create([
        'kode' => 'JK01',
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => '16:00:00',
    ]);

    foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
        KonfigurasiJamKerja::factory()->create([
            'id_pegawai' => $pegawai->id,
            'id_jam_kerja' => $shift->id,
            'hari' => $hari,
        ]);
    }

    $cuti = Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);

    SaldoCuti::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2025,
        'saldo' => 6,
    ]);

    $this->actingAs($user)
        ->post(route('pengajuan.store'), [
            'jenis' => 'cuti',
            'id_cuti' => $cuti->id,
            'tanggal_mulai' => '2026-12-01',
            'tanggal_berakhir' => '2026-12-02',
            'keterangan' => 'Cuti tahunan alur penuh',
        ])
        ->assertRedirect(route('pengajuan.index'))
        ->assertSessionHasNoErrors();

    $pengajuan = Pengajuan::query()->where('keterangan', 'Cuti tahunan alur penuh')->firstOrFail();

    expect($pengajuan->status)->toBe('pending')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan), ['catatan_setujui' => 'Disetujui.'])
        ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
        ->assertSessionHasNoErrors();

    $pengajuan->refresh();

    expect($pengajuan->status)->toBe('approved')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->pluck('status')->all())
        ->toBe(['cuti', 'cuti'])
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(2);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.cancel', $pengajuan))
        ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
        ->assertSessionHas('success');

    $pengajuan->refresh();

    expect($pengajuan->status)->toBe('pending')
        ->and($pengajuan->disetujui_oleh)->toBeNull()
        ->and($pengajuan->disetujui_pada)->toBeNull()
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});
