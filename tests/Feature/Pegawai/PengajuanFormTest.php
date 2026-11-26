<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\SaldoCuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
    $this->withoutVite();
    Storage::fake('local');

    $this->cuti = Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
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
    $this->actingAs($this->user)->get(route('pengajuan.create'))
        ->assertOk()
        ->assertSee('Form pengajuan')
        ->assertSee(route('pengajuan.store'), false)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('name="jenis"', false)
        ->assertSee('name="id_cuti"', false)
        ->assertSee('name="mode_dl"', false)
        ->assertSee('id="blok-jenis-cuti"', false)
        ->assertSee('id="blok-mode-dl"', false)
        ->assertSee('id="blok-alasan-haji"', false)
        ->assertSee('name="tanggal_mulai"', false)
        ->assertSee('name="tanggal_berakhir"', false)
        ->assertSee('name="alasan_haji"', false)
        ->assertSee('name="keterangan"', false)
        ->assertSee('name="lampiran"', false)
        ->assertSee('C01')
        ->assertSee('Sisa kuota tahun 2026')
        ->assertSee('Wajib untuk Sakit lebih dari 3 hari kerja');
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
        ->and($pengajuan->mode_dl)->toBeNull()
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

it('requires a lampiran when sakit lasts more than three working days', function () {
    $this->actingAs($this->user)
        ->postJson(route('pengajuan.store'), ($this->payload)([
            'jenis' => 'sakit',
            'id_cuti' => null,
            'tanggal_mulai' => '2026-11-16',
            'tanggal_berakhir' => '2026-11-20',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lampiran');
});

it('accepts a short sakit without a lampiran', function () {
    $this->actingAs($this->user)
        ->post(route('pengajuan.store'), ($this->payload)([
            'jenis' => 'sakit',
            'id_cuti' => null,
            'tanggal_mulai' => '2026-11-18',
            'tanggal_berakhir' => '2026-11-20',
            'keterangan' => 'Demam tiga hari',
        ]))
        ->assertRedirect(route('pengajuan.index'))
        ->assertSessionHasNoErrors();

    expect(Pengajuan::query()->where('keterangan', 'Demam tiga hari')->exists())->toBeTrue();
});

it('warns an unlinked account and refuses to store its pengajuan', function () {
    $tanpaPegawai = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($tanpaPegawai)->get(route('pengajuan.create'))
        ->assertOk()
        ->assertSee('Data pegawai belum tersedia');

    $this->actingAs($tanpaPegawai)
        ->postJson(route('pengajuan.store'), ($this->payload)())
        ->assertForbidden();
});

it('keeps admin on the admin side of the application', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('pengajuan.create'))
        ->assertRedirect(route('admin.dashboard'));
});

it('requires mode_dl for dinas luar and stores the chosen mode', function () {
    $this->actingAs($this->user)
        ->postJson(route('pengajuan.store'), ($this->payload)([
            'jenis' => 'dl',
            'id_cuti' => null,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mode_dl');

    $this->actingAs($this->user)
        ->post(route('pengajuan.store'), ($this->payload)([
            'jenis' => 'dl',
            'id_cuti' => null,
            'mode_dl' => 'setengah',
            'keterangan' => 'Dinas luar setengah hari',
        ]))
        ->assertRedirect(route('pengajuan.index'));

    $pengajuan = Pengajuan::query()->where('keterangan', 'Dinas luar setengah hari')->firstOrFail();

    expect($pengajuan->jenis)->toBe('dl')
        ->and($pengajuan->mode_dl)->toBe('setengah')
        ->and($pengajuan->id_cuti)->toBeNull()
        ->and($pengajuan->lampiran)->toBeNull();
});

it('rejects mode_dl outside dinas luar', function () {
    $this->actingAs($this->user)
        ->postJson(route('pengajuan.store'), ($this->payload)([
            'mode_dl' => 'full',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('mode_dl');
});

it('shows the haji checkbox only when the selected cuti is C04', function () {
    $c04 = Cuti::factory()->create([
        'kode' => 'C04',
        'nama' => 'Cuti Besar',
        'maks_hari' => 90,
        'kuota' => 90,
        'maks_bawa' => 0,
    ]);

    $cekBlok = function (string $html): array {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $blok = $xpath->query('//*[@id="blok-alasan-haji"]')->item(0);

        return [
            'tersembunyi' => $blok instanceof DOMElement && str_contains($blok->getAttribute('class'), 'hidden'),
            'disabled' => $xpath->query('//*[@id="alasan_haji"]')->item(0)?->hasAttribute('disabled') ?? true,
        ];
    };

    $default = $this->actingAs($this->user)->get(route('pengajuan.create'))->getContent();
    $denganC04 = $this->actingAs($this->user)->withSession([
        '_old_input' => ['jenis' => 'cuti', 'id_cuti' => (string) $c04->id],
    ])->get(route('pengajuan.create'))->getContent();
    $denganC01 = $this->actingAs($this->user)->withSession([
        '_old_input' => ['jenis' => 'cuti', 'id_cuti' => (string) $this->cuti->id],
    ])->get(route('pengajuan.create'))->getContent();

    expect($cekBlok($default))->toBe(['tersembunyi' => true, 'disabled' => true])
        ->and($cekBlok($denganC04))->toBe(['tersembunyi' => false, 'disabled' => false])
        ->and($cekBlok($denganC01))->toBe(['tersembunyi' => true, 'disabled' => true]);
});

it('shows 24 days including savings on the form', function () {
    SaldoCuti::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cuti->id,
        'tahun_sumber' => 2024,
        'saldo' => 6,
    ]);
    SaldoCuti::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cuti->id,
        'tahun_sumber' => 2025,
        'saldo' => 6,
    ]);

    $this->actingAs($this->user)->get(route('pengajuan.create'))
        ->assertOk()
        ->assertSee('Total tersedia termasuk tabungan:')
        ->assertSee('24 hari')
        ->assertSee('maksimal terbawa ke 2027:')
        ->assertSee('6 hari');
});

it('redirects a new cuti tahunan to the pending one instead of creating a duplicate', function () {
    $pending = Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cuti->id,
        'tanggal_mulai' => '2026-11-10',
        'tanggal_berakhir' => '2026-11-12',
        'jenis' => 'cuti',
        'keterangan' => 'Cuti tahunan pending',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->post(route('pengajuan.store'), ($this->payload)())
        ->assertRedirect(route('pengajuan.edit', $pending))
        ->assertSessionHas('warning');

    expect(Pengajuan::count())->toBe(1)
        ->and($pending->fresh()->tanggal_mulai)->toBe('2026-11-10');
});

it('renders the edit form with the pending pengajuan values', function () {
    $pending = Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cuti->id,
        'tanggal_mulai' => '2026-11-10',
        'tanggal_berakhir' => '2026-11-12',
        'jenis' => 'cuti',
        'keterangan' => 'Cuti tahunan pending',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)->get(route('pengajuan.edit', $pending))
        ->assertOk()
        ->assertSee('Ubah pengajuan')
        ->assertSee('value="2026-11-10"', false)
        ->assertSee('value="2026-11-12"', false)
        ->assertSee('Cuti tahunan pending')
        ->assertSee(route('pengajuan.update', $pending), false);
});

it('updates a pending pengajuan and keeps it pending', function () {
    $pending = Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cuti->id,
        'tanggal_mulai' => '2026-11-10',
        'tanggal_berakhir' => '2026-11-12',
        'jenis' => 'cuti',
        'keterangan' => 'Cuti tahunan pending',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->put(route('pengajuan.update', $pending), ($this->payload)([
            'tanggal_mulai' => '2026-11-23',
            'tanggal_berakhir' => '2026-11-24',
            'keterangan' => 'Cuti tahunan diperbaiki',
        ]))
        ->assertRedirect(route('pengajuan.show', $pending))
        ->assertSessionHas('success');

    $pending->refresh();

    expect($pending->tanggal_mulai)->toBe('2026-11-23')
        ->and($pending->tanggal_berakhir)->toBe('2026-11-24')
        ->and($pending->keterangan)->toBe('Cuti tahunan diperbaiki')
        ->and($pending->status)->toBe('pending')
        ->and($pending->id_pegawai)->toBe($this->pegawai->id);
});

it('rejects editing a pengajuan that has already been decided', function () {
    $approved = Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cuti->id,
        'jenis' => 'cuti',
        'status' => 'approved',
    ]);

    $this->actingAs($this->user)->get(route('pengajuan.edit', $approved))->assertForbidden();
});

it('rejects editing another pegawai pengajuan', function () {
    $lainUser = User::factory()->create(['role' => 'pegawai']);
    $lainPegawai = Pegawai::factory()->create(['user_id' => $lainUser->id]);
    $pengajuanLain = Pengajuan::factory()->create([
        'id_pegawai' => $lainPegawai->id,
        'id_cuti' => $this->cuti->id,
        'jenis' => 'cuti',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)->get(route('pengajuan.edit', $pengajuanLain))->assertForbidden();
});

it('replaces the lampiran when a new file is uploaded', function () {
    Storage::disk('local')->put('lampiran-pengajuan/lama.pdf', 'lama');

    $pending = Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => null,
        'jenis' => 'sakit',
        'tanggal_mulai' => '2026-11-10',
        'tanggal_berakhir' => '2026-11-10',
        'keterangan' => 'Sakit pending',
        'lampiran' => 'lampiran-pengajuan/lama.pdf',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->put(route('pengajuan.update', $pending), [
            'jenis' => 'sakit',
            'id_cuti' => null,
            'tanggal_mulai' => '2026-11-10',
            'tanggal_berakhir' => '2026-11-10',
            'keterangan' => 'Sakit pending',
            'lampiran' => UploadedFile::fake()->create('baru.pdf', 50, 'application/pdf'),
        ])
        ->assertRedirect(route('pengajuan.show', $pending))
        ->assertSessionHasNoErrors();

    $pathBaru = $pending->fresh()->lampiran;

    expect($pathBaru)->not->toBe('lampiran-pengajuan/lama.pdf');
    Storage::disk('local')->assertMissing('lampiran-pengajuan/lama.pdf');
    Storage::disk('local')->assertExists($pathBaru);
});

it('keeps the existing lampiran when no new file is uploaded', function () {
    Storage::disk('local')->put('lampiran-pengajuan/lama.pdf', 'lama');

    $pending = Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => null,
        'jenis' => 'sakit',
        'tanggal_mulai' => '2026-11-10',
        'tanggal_berakhir' => '2026-11-10',
        'keterangan' => 'Sakit pending',
        'lampiran' => 'lampiran-pengajuan/lama.pdf',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->put(route('pengajuan.update', $pending), [
            'jenis' => 'sakit',
            'id_cuti' => null,
            'tanggal_mulai' => '2026-11-10',
            'tanggal_berakhir' => '2026-11-10',
            'keterangan' => 'Sakit pending diperbaiki',
        ])
        ->assertRedirect(route('pengajuan.show', $pending))
        ->assertSessionHasNoErrors();

    expect($pending->fresh()->lampiran)->toBe('lampiran-pengajuan/lama.pdf');
    Storage::disk('local')->assertExists('lampiran-pengajuan/lama.pdf');
});
