<?php

use App\Models\Pegawai;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('seeds accounts that can log in as admin and pegawai', function () {
    $this->seed();

    $this->post(route('login.store'), [
        'identitas' => 'admin@esensi.test',
        'password' => 'password',
    ])->assertRedirect('/admin/dashboard');

    auth()->logout();

    $this->post(route('login.store'), [
        'identitas' => 'pegawai@esensi.test',
        'password' => 'password',
    ])->assertRedirect('/dashboard');
});

it('renders the main admin pages from seeded data', function () {
    $this->seed();
    $admin = User::query()->where('email', 'admin@esensi.test')->firstOrFail();

    foreach ([
        'admin.dashboard',
        'admin.monitoring.index',
        'admin.laporan.index',
        'admin.rekap.index',
        'admin.pengajuan.index',
        'admin.pegawai.index',
    ] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk();
    }
});

it('renders the main pegawai pages from seeded data', function () {
    $this->seed();
    $pegawai = User::query()->where('email', 'pegawai@esensi.test')->firstOrFail();

    foreach ([
        'dashboard',
        'presensi.index',
        'histori.index',
        'pengajuan.index',
        'pengajuan.create',
        'profil',
        'password.edit',
    ] as $route) {
        $this->actingAs($pegawai)->get(route($route))->assertOk();
    }
});

it('can download seeded laporan exports', function () {
    $this->seed();
    $admin = User::query()->where('email', 'admin@esensi.test')->firstOrFail();
    $pegawai = Pegawai::query()->where('nip', '199302032022031001')->firstOrFail();

    $this->actingAs($admin)->get(route('admin.laporan.export.excel', [
        'id_pegawai' => $pegawai->id,
        'bulan' => CarbonImmutable::now((string) config('presensi.zona_waktu'))->month,
        'tahun' => CarbonImmutable::now((string) config('presensi.zona_waktu'))->year,
    ]))->assertOk()->assertDownload();

    $this->actingAs($admin)->get(route('admin.laporan.export.pdf', [
        'bulan' => CarbonImmutable::now((string) config('presensi.zona_waktu'))->month,
        'tahun' => CarbonImmutable::now((string) config('presensi.zona_waktu'))->year,
    ]))->assertOk()->assertDownload();
});
