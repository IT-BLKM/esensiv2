<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
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
        ->assertSee('Sisa kuota tahun 2026');
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
