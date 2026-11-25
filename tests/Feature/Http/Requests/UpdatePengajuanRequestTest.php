<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);

    $user = User::factory()->create(['role' => 'pegawai']);
    $this->pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $this->user = $user;
    $this->cuti = Cuti::factory()->create(['kode' => 'C01', 'nama' => 'Cuti Tahunan']);
    $this->pengajuan = Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cuti->id,
        'tanggal_mulai' => '2026-12-01',
        'tanggal_berakhir' => '2026-12-02',
        'jenis' => 'cuti',
        'status' => 'pending',
    ]);
});

it('rejects an id_cuti that does not exist on update', function () {
    $this->actingAs($this->user)
        ->putJson(route('pegawai.pengajuan.update', $this->pengajuan), [
            'jenis' => 'cuti',
            'id_cuti' => 999999,
            'tanggal_mulai' => '2026-12-01',
            'tanggal_berakhir' => '2026-12-02',
            'keterangan' => 'Tetap pending',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');

    expect($this->pengajuan->fresh()->keterangan)->not->toBe('Tetap pending');
});

it('requires mode_dl for dinas luar on update', function () {
    $this->actingAs($this->user)
        ->putJson(route('pegawai.pengajuan.update', $this->pengajuan), [
            'jenis' => 'dl',
            'id_cuti' => null,
            'tanggal_mulai' => '2026-12-01',
            'tanggal_berakhir' => '2026-12-02',
            'keterangan' => 'Dinas luar',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mode_dl');
});
