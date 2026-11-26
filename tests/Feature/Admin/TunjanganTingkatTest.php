<?php

use App\Actions\Laporan\HitungPotonganTunjanganKinerjaAction;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\TunjanganTingkat;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function adminTunjangan(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('seeds the default levels on the first admin visit', function () {
    $this->actingAs(adminTunjangan())->get(route('admin.tunjangan.index'))
        ->assertOk()
        ->assertSee('Aturan tingkat potongan TUKIN')
        ->assertSee('TL1')
        ->assertSee('PSW4');

    expect(TunjanganTingkat::query()->count())->toBe(7);
});

it('lets an admin update a level and changes the tukin calculation', function () {
    $this->actingAs(adminTunjangan())->get(route('admin.tunjangan.index'))->assertOk();
    $tl1 = TunjanganTingkat::query()->where('kategori', 'keterlambatan')->where('level', 'TL1')->firstOrFail();

    $this->actingAs(adminTunjangan())
        ->put(route('admin.tunjangan.update'), [
            'tingkat' => [
                $tl1->id => ['min' => 1, 'max' => 30, 'persen' => 2.0],
            ],
        ])
        ->assertRedirect(route('admin.tunjangan.index'))
        ->assertSessionHas('success');

    expect((float) $tl1->refresh()->persen)->toBe(2.0);

    $pegawai = Pegawai::factory()->create();
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

    $hasil = app(HitungPotonganTunjanganKinerjaAction::class)->bulanan($pegawai, 2026, 11);

    expect($hasil['ringkasan']['totalPersenTl'])->toBe(2.0);
});

it('rejects an invalid level range', function () {
    $this->actingAs(adminTunjangan())->get(route('admin.tunjangan.index'))->assertOk();
    $tl1 = TunjanganTingkat::query()->where('kategori', 'keterlambatan')->where('level', 'TL1')->firstOrFail();

    $this->actingAs(adminTunjangan())
        ->put(route('admin.tunjangan.update'), [
            'tingkat' => [
                $tl1->id => ['min' => 31, 'max' => 10, 'persen' => 1],
            ],
        ])
        ->assertSessionHasErrors([
            "tingkat.{$tl1->id}.max" => 'Batas atas tidak boleh kurang dari batas bawah.',
        ]);
});

it('forbids pegawai from changing tukin levels', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.tunjangan.index'))->assertRedirect('/dashboard');
});
