<?php

use App\Models\HariLibur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function adminHariLibur(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('redirects guests and pegawai away from hari libur pages', function () {
    $this->get(route('admin.hari-libur.index'))->assertRedirect(route('login'));
    $this->post(route('admin.hari-libur.store'), [
        'tanggal' => '2026-12-25',
        'nama' => 'Natal',
    ])->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.hari-libur.index'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->post(route('admin.hari-libur.store'), [
        'tanggal' => '2026-12-25',
        'nama' => 'Natal',
    ])->assertRedirect('/dashboard');
});

it('shows the holiday calendar', function () {
    $libur = HariLibur::factory()->create([
        'tanggal' => '2026-12-25',
        'nama' => 'Hari Raya Natal',
    ]);

    $this->actingAs(adminHariLibur())->get(route('admin.hari-libur.index'))
        ->assertOk()
        ->assertSee('Hari libur nasional')
        ->assertSee('Hari Raya Natal')
        ->assertSee(Carbon::parse($libur->tanggal)->locale('id')->translatedFormat('l, d F Y'));
});

it('warns when the current year has no holiday yet', function () {
    $tahunIni = Carbon::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;

    HariLibur::factory()->create([
        'tanggal' => ($tahunIni - 2).'-01-01',
        'nama' => 'Libur lama',
    ]);

    $this->actingAs(adminHariLibur())->get(route('admin.hari-libur.index'))
        ->assertOk()
        ->assertSee('Belum ada hari libur '.$tahunIni);
});

it('lets an admin create a holiday', function () {
    $this->actingAs(adminHariLibur())
        ->post(route('admin.hari-libur.store'), [
            'tanggal' => '2026-12-25',
            'nama' => 'Hari Raya Natal',
        ])
        ->assertRedirect(route('admin.hari-libur.index'))
        ->assertSessionHas('success', 'Hari libur Hari Raya Natal berhasil ditambahkan.');

    $this->assertDatabaseHas('hari_libur', [
        'tanggal' => '2026-12-25',
        'nama' => 'Hari Raya Natal',
    ]);
});

it('lets an admin update a holiday', function () {
    $libur = HariLibur::factory()->create([
        'tanggal' => '2026-12-25',
        'nama' => 'Natal',
    ]);

    $this->actingAs(adminHariLibur())
        ->put(route('admin.hari-libur.update', $libur), [
            'tanggal' => '2026-12-26',
            'nama' => 'Cuti bersama Natal',
        ])
        ->assertRedirect(route('admin.hari-libur.index'))
        ->assertSessionHas('success', 'Hari libur Cuti bersama Natal berhasil diperbarui.');

    expect((string) $libur->refresh()->tanggal)->toBe('2026-12-26')
        ->and($libur->nama)->toBe('Cuti bersama Natal');
});

it('lets an admin delete a holiday', function () {
    $libur = HariLibur::factory()->create();

    $this->actingAs(adminHariLibur())
        ->delete(route('admin.hari-libur.destroy', $libur))
        ->assertRedirect(route('admin.hari-libur.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('hari_libur', ['id' => $libur->id]);
});

it('rejects invalid or duplicate holiday dates', function () {
    HariLibur::factory()->create(['tanggal' => '2026-12-25']);

    $this->actingAs(adminHariLibur())
        ->post(route('admin.hari-libur.store'), [
            'tanggal' => '2026-12-25',
            'nama' => 'Duplikat',
        ])
        ->assertSessionHasErrors([
            'tanggal' => 'Tanggal ini sudah terdaftar sebagai hari libur.',
        ]);

    $this->actingAs(adminHariLibur())
        ->post(route('admin.hari-libur.store'), [
            'tanggal' => '',
            'nama' => '',
        ])
        ->assertSessionHasErrors([
            'tanggal' => 'Tanggal libur wajib diisi.',
            'nama' => 'Nama hari libur wajib diisi.',
        ]);
});
