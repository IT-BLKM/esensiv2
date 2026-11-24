<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Layout admin (issue #23): header, sidebar menu, pesan sesi, footer.
 * Semua halaman admin melewatinya, jadi cukup diuji lewat render nyata.
 */
beforeEach(fn () => $this->withoutVite());

it('renders the header, sidebar and footer around the admin content', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('id="sidebar"', false)
        ->assertSee('<footer', false)
        ->assertSee('<title>Dashboard — '.config('app.name').'</title>', false)
        ->assertSee('Dashboard admin')
        ->assertSee(route('logout'), false)
        ->assertSee($admin->nama);
});

it('lists every admin menu entry as a named route', function (string $label, string $namaRoute) {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route($namaRoute), false)
        ->assertSee($label, false);
})->with([
    'dashboard' => ['Dashboard', 'admin.dashboard'],
    'pegawai' => ['Pegawai', 'admin.pegawai.index'],
    'departemen' => ['Departemen', 'admin.departemen.index'],
    'cuti' => ['Cuti', 'admin.cuti.index'],
    'monitoring' => ['Monitoring', 'admin.monitoring.index'],
    'pengajuan' => ['Pengajuan', 'admin.pengajuan.index'],
    'laporan' => ['Laporan', 'admin.laporan.index'],
    'konfigurasi' => ['Konfigurasi', 'admin.konfigurasi.index'],
]);

it('marks the menu entry of the page being visited', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.pegawai.index'))
        ->assertOk()
        ->assertSee('href="'.route('admin.pegawai.index').'" aria-current="page"', false)
        ->assertDontSee('href="'.route('admin.cuti.index').'" aria-current="page"', false);
});

it('renders every placeholder admin page inside the layout', function (string $namaRoute, string $judul) {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route($namaRoute))
        ->assertOk()
        ->assertSee('id="sidebar"', false)
        ->assertSee($judul, false);
})->with([
    'pegawai' => ['admin.pegawai.index', 'Pegawai'],
    'departemen' => ['admin.departemen.index', 'Departemen'],
    'cuti' => ['admin.cuti.index', 'Cuti'],
    'monitoring' => ['admin.monitoring.index', 'Monitoring'],
    'pengajuan' => ['admin.pengajuan.index', 'Pengajuan'],
    'laporan' => ['admin.laporan.index', 'Laporan'],
    'konfigurasi' => ['admin.konfigurasi.index', 'Konfigurasi'],
]);

it('sends a guest to the login page', function () {
    $this->get(route('admin.pegawai.index'))->assertRedirect(route('login'));
});

it('sends a pegawai to their own dashboard with a warning', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.pegawai.index'))
        ->assertRedirect('/dashboard')
        ->assertSessionHas('warning', 'Halaman ini hanya untuk akun admin.');
});

it('shows flashed session messages above the content', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->withSession(['warning' => 'Saldo cuti tinggal 2 hari.'])
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Saldo cuti tinggal 2 hari.');
});
