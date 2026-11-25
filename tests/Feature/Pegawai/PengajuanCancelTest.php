<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-26 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);

    $this->user = User::factory()->create(['role' => 'pegawai']);
    $this->pegawai = Pegawai::factory()->create(['user_id' => $this->user->id]);
});

function buatPengajuanCancel(Pegawai $pegawai, string $jenis, string $status = 'pending'): Pengajuan
{
    $atribut = [
        'id_pegawai' => $pegawai->id,
        'tanggal_mulai' => '2026-12-01',
        'tanggal_berakhir' => '2026-12-02',
        'jenis' => $jenis,
        'keterangan' => 'Pengajuan untuk dibatalkan',
        'status' => $status,
    ];

    return match ($jenis) {
        'cuti' => Pengajuan::factory()->create([
            ...$atribut,
            'id_cuti' => Cuti::factory()->create(['kode' => 'C01'])->id,
        ]),
        'sakit' => Pengajuan::factory()->sakit()->create($atribut),
        'dl' => Pengajuan::factory()->dl('setengah')->create($atribut),
        default => throw new InvalidArgumentException('Jenis tidak dikenal.'),
    };
}

it('lets a pegawai cancel a pending pengajuan', function (string $jenis) {
    $pengajuan = buatPengajuanCancel($this->pegawai, $jenis);

    $this->actingAs($this->user)
        ->post(route('pengajuan.cancel', $pengajuan))
        ->assertRedirect(route('pengajuan.index'))
        ->assertSessionHas('success', 'Pengajuan berhasil dibatalkan.');

    $this->assertModelMissing($pengajuan);
})->with(['cuti', 'sakit', 'dl']);

it('shows the cancel action only for pending pengajuan', function () {
    $pending = buatPengajuanCancel($this->pegawai, 'cuti');
    $approved = buatPengajuanCancel($this->pegawai, 'sakit', 'approved');

    $this->actingAs($this->user)->get(route('pengajuan.show', $pending))
        ->assertOk()
        ->assertSee('Batalkan pengajuan');

    $this->actingAs($this->user)->get(route('pengajuan.show', $approved))
        ->assertOk()
        ->assertDontSee('Batalkan pengajuan');
});

it('refuses to cancel approved or rejected pengajuan', function (string $status) {
    $pengajuan = buatPengajuanCancel($this->pegawai, 'cuti', $status);

    $this->actingAs($this->user)
        ->post(route('pengajuan.cancel', $pengajuan))
        ->assertForbidden();

    $this->assertModelExists($pengajuan);
})->with(['approved', 'rejected']);

it('refuses to cancel another pegawai pengajuan', function () {
    $lainUser = User::factory()->create(['role' => 'pegawai']);
    $lainPegawai = Pegawai::factory()->create(['user_id' => $lainUser->id]);
    $pengajuan = buatPengajuanCancel($lainPegawai, 'dl');

    $this->actingAs($this->user)
        ->post(route('pengajuan.cancel', $pengajuan))
        ->assertForbidden();

    $this->assertModelExists($pengajuan);
});

it('redirects guests before cancelling a pengajuan', function () {
    $pengajuan = buatPengajuanCancel($this->pegawai, 'sakit');

    $this->post(route('pengajuan.cancel', $pengajuan))->assertRedirect(route('login'));
});
