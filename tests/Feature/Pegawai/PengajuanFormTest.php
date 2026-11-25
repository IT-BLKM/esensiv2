<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');

    $this->cuti = Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'kuota' => 12,
    ]);

    $this->user = User::factory()->create(['role' => 'pegawai']);
    $this->pegawai = Pegawai::factory()->create(['user_id' => $this->user->id]);

    $this->payload = fn (array $overrides = []) => array_merge([
        'jenis' => 'cuti',
        'id_cuti' => $this->cuti->id,
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-22',
        'keterangan' => 'Cuti tahunan',
    ], $overrides);
});

it('redirects guests away from the pengajuan form', function () {
    $this->get(route('pengajuan.index'))->assertRedirect(route('login'));
    $this->get(route('pengajuan.create'))->assertRedirect(route('login'));
    $this->post(route('pengajuan.store'), ($this->payload)())->assertRedirect(route('login'));
});

it('renders the multipart form with every pengajuan field', function () {
    $this->actingAs($this->user)->get(route('pengajuan.index'))
        ->assertOk()
        ->assertSee('Form pengajuan')
        ->assertSee(route('pengajuan.store'), false)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="jenis"', false)
        ->assertSee('name="id_cuti"', false)
        ->assertSee('name="tanggal_mulai"', false)
        ->assertSee('name="tanggal_berakhir"', false)
        ->assertSee('name="keterangan"', false)
        ->assertSee('name="lampiran"', false)
        ->assertSee('C01');
});

it('rejects jenis cuti without id_cuti with a validation error', function () {
    $this->actingAs($this->user)
        ->postJson(route('pengajuan.store'), ($this->payload)(['id_cuti' => null]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');

    $this->assertDatabaseCount('pengajuan', 0);
});

it('stores a pending pengajuan owned by the logged in pegawai with a private lampiran', function () {
    $this->actingAs($this->user)
        ->post(route('pengajuan.store'), ($this->payload)([
            'jenis' => 'sakit',
            'id_cuti' => null,
            'keterangan' => 'Surat dokter',
            'lampiran' => UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf'),
        ]))
        ->assertRedirect(route('pengajuan.index'))
        ->assertSessionHas('success');

    $pengajuan = Pengajuan::query()->where('keterangan', 'Surat dokter')->firstOrFail();

    expect($pengajuan->id_pegawai)->toBe($this->pegawai->id)
        ->and($pengajuan->id_cuti)->toBeNull()
        ->and($pengajuan->jenis)->toBe('sakit')
        ->and($pengajuan->status)->toBe('pending')
        ->and($pengajuan->disetujui_oleh)->toBeNull()
        ->and($pengajuan->disetujui_pada)->toBeNull();

    Storage::disk('local')->assertExists($pengajuan->lampiran);
});

it('rejects client supplied owner and status', function () {
    $this->actingAs($this->user)
        ->postJson(route('pengajuan.store'), ($this->payload)([
            'id_pegawai' => 999999,
            'status' => 'approved',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_pegawai', 'status']);

    $this->assertDatabaseCount('pengajuan', 0);
});

it('requires a lampiran when the pengajuan is sakit', function () {
    $this->actingAs($this->user)
        ->postJson(route('pengajuan.store'), ($this->payload)([
            'jenis' => 'sakit',
            'id_cuti' => null,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lampiran');
});

it('warns an unlinked account and refuses to store its pengajuan', function () {
    $tanpaPegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($tanpaPegawai)->get(route('pengajuan.index'))
        ->assertOk()
        ->assertSee('Data pegawai belum tersedia');

    $this->actingAs($tanpaPegawai)
        ->postJson(route('pengajuan.store'), ($this->payload)())
        ->assertForbidden();
});

it('keeps admin on the admin side of the application', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('pengajuan.index'))
        ->assertRedirect(route('admin.dashboard'));
});
