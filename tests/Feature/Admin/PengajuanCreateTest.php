<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);

    $this->cuti = Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);

    $this->admin = User::factory()->create(['role' => 'admin']);

    $userTarget = User::factory()->create(['role' => 'pegawai']);
    $this->target = Pegawai::factory()->create(['user_id' => $userTarget->id]);

    $this->payload = fn (array $overrides = []) => array_merge([
        'id_pegawai' => $this->target->id,
        'jenis' => 'cuti',
        'id_cuti' => $this->cuti->id,
        'tanggal_mulai' => '2026-11-23',
        'tanggal_berakhir' => '2026-11-24',
        'keterangan' => 'Cuti tahunan dari admin',
    ], $overrides);
});

it('redirects guests away from the admin pengajuan create form', function () {
    $this->get(route('admin.pengajuan.create'))->assertRedirect(route('login'));
    $this->post(route('admin.pengajuan.store'), ($this->payload)())->assertRedirect(route('login'));
});

it('keeps a pegawai out of the admin pengajuan create form', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($pegawai)->get(route('admin.pengajuan.create'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->post(route('admin.pengajuan.store'), ($this->payload)())
        ->assertRedirect('/dashboard');
});

it('renders the admin form with a pegawai selector and all pengajuan fields', function () {
    $this->actingAs($this->admin)->get(route('admin.pengajuan.create', ['id_pegawai' => $this->target->id]))
        ->assertOk()
        ->assertSee('Buat pengajuan pegawai')
        ->assertSee('name="id_pegawai"', false)
        ->assertSee($this->target->nama)
        ->assertSee('name="jenis"', false)
        ->assertSee('name="id_cuti"', false)
        ->assertSee('name="tanggal_mulai"', false)
        ->assertSee('name="lampiran"', false)
        ->assertSee(route('admin.pengajuan.store'), false);
});

it('creates a pending pengajuan for the selected pegawai without creating presensi', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.pengajuan.store'), ($this->payload)())
        ->assertRedirect()
        ->assertSessionHas('success');

    $pengajuan = Pengajuan::query()->firstOrFail();

    expect($pengajuan->id_pegawai)->toBe($this->target->id)
        ->and($pengajuan->jenis)->toBe('cuti')
        ->and($pengajuan->id_cuti)->toBe($this->cuti->id)
        ->and($pengajuan->status)->toBe('pending')
        ->and($pengajuan->disetujui_oleh)->toBeNull()
        ->and($pengajuan->disetujui_pada)->toBeNull()
        ->and(Presensi::count())->toBe(0);
});

it('requires a valid selected pegawai', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.pengajuan.store'), ($this->payload)(['id_pegawai' => null]))
        ->assertSessionHasErrors(['id_pegawai' => 'Pegawai wajib dipilih.']);

    $this->actingAs($this->admin)
        ->post(route('admin.pengajuan.store'), ($this->payload)(['id_pegawai' => 999999]))
        ->assertSessionHasErrors(['id_pegawai' => 'Pegawai tidak ditemukan.']);

    expect(Pengajuan::count())->toBe(0);
});

it('validates collisions against the selected pegawai', function () {
    Presensi::factory()->create([
        'id_pegawai' => $this->target->id,
        'tgl_presensi' => '2026-11-23',
        'status' => 'hadir',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.pengajuan.store'), ($this->payload)())
        ->assertSessionHasErrors('tanggal_mulai');

    expect(Pengajuan::count())->toBe(0);
});

it('shows the new pending pengajuan in the admin list', function () {
    $this->actingAs($this->admin)->post(route('admin.pengajuan.store'), ($this->payload)());

    $this->actingAs($this->admin)->get(route('admin.pengajuan.index'))
        ->assertOk()
        ->assertSee($this->target->nama)
        ->assertSee('Cuti Tahunan');
});
