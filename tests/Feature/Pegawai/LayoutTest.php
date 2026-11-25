<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Layout pegawai (issue #24): header ringkas + bottom navigation lima menu,
 * mobile-first. Semua halaman pegawai melewatinya.
 */
beforeEach(fn () => $this->withoutVite());

it('renders the header and bottom navigation around the content', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="bottom-nav"', false)
        ->assertSee('Dashboard pegawai')
        ->assertSee(route('logout'), false)
        ->assertSee($pegawai->nama);
});

it('lists every bottom navigation entry as a named route', function (string $label, string $namaRoute) {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $halaman = $this->actingAs($pegawai)->get(route('dashboard'))->assertOk()->getContent();

    // Tautan dan label dicek di dalam potongan <nav> saja, bukan di seluruh halaman.
    $nav = substr($halaman, strpos($halaman, 'id="bottom-nav"'));

    expect($nav)
        ->toContain(route($namaRoute))
        ->toContain($label);
})->with([
    'dashboard' => ['Dashboard', 'dashboard'],
    'presensi' => ['Presensi', 'presensi.index'],
    'histori' => ['Histori', 'histori.index'],
    'pengajuan' => ['Pengajuan', 'pengajuan.index'],
    'profil' => ['Profil', 'profil'],
]);

it('marks the navigation entry of the page being visited', function (string $namaRoute) {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route($namaRoute))
        ->assertOk()
        ->assertSee('href="'.route($namaRoute).'" aria-current="page"', false);
})->with([
    'dashboard' => 'dashboard',
    'presensi' => 'presensi.index',
    'histori' => 'histori.index',
    'pengajuan' => 'pengajuan.index',
    'profil' => 'profil',
]);

it('renders every placeholder pegawai page inside the layout', function (string $namaRoute, string $judul) {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route($namaRoute))
        ->assertOk()
        ->assertSee('id="bottom-nav"', false)
        ->assertSee($judul, false);
})->with([
    'dashboard' => ['dashboard', 'Dashboard pegawai'],
    'presensi' => ['presensi.index', 'Presensi'],
    'histori' => ['histori.index', 'Histori presensi'],
    'pengajuan' => ['pengajuan.index', 'Pengajuan'],
    'profil' => ['profil', 'Profil'],
]);

it('shows the account on the profile page with a link to change the password', function () {
    $pegawai = User::factory()->create([
        'role' => 'pegawai',
        'nama' => 'Budi Santoso',
        'email' => 'budi@presensi.test',
    ]);

    $this->actingAs($pegawai)->get(route('profil'))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('budi@presensi.test')
        ->assertSee(route('password.edit'), false);
});

it('sends a guest to the login page', function () {
    $this->get(route('presensi.index'))->assertRedirect(route('login'));
});

it('sends an admin to the admin dashboard with a warning', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('pengajuan.index'))
        ->assertRedirect('/admin/dashboard')
        ->assertSessionHas('warning', 'Halaman ini hanya untuk akun pegawai.');
});

it('shows flashed session messages above the content', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)
        ->withSession(['success' => 'Password berhasil diganti.'])
        ->get(route('profil'))
        ->assertOk()
        ->assertSee('Password berhasil diganti.');
});
