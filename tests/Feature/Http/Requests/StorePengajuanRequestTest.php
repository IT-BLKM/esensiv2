<?php

use App\Http\Requests\StorePengajuanRequest;
use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
    Storage::fake('local');

    // URI khusus test: route aplikasi /pengajuan sudah dipakai form #36.
    Route::post('/test/validasi-pengajuan', function (StorePengajuanRequest $request) {
        $data = $request->validated();

        if (($data['lampiran'] ?? null) instanceof UploadedFile) {
            $data['lampiran'] = $data['lampiran']->getClientOriginalName();
        }

        return response()->json($data, 201);
    });

    $this->cutiTahunan = Cuti::factory()->create([
        'kode' => 'C01',
        'nama' => 'Cuti Tahunan',
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => 6,
    ]);
    $this->cutiPenting = Cuti::factory()->create([
        'kode' => 'C03',
        'nama' => 'Cuti Alasan Penting',
        'maks_hari' => 30,
        'kuota' => 30,
        'maks_bawa' => 0,
    ]);
    $this->cutiBesar = Cuti::factory()->create([
        'kode' => 'C04',
        'nama' => 'Cuti Besar',
        'maks_hari' => 90,
        'kuota' => 90,
        'maks_bawa' => 0,
    ]);
    $this->cutiKuotaKecil = Cuti::factory()->create([
        'kode' => 'C05',
        'nama' => 'Cuti Kuota Kecil',
        'maks_hari' => 1,
        'kuota' => 0,
        'maks_bawa' => 0,
    ]);

    $user = User::factory()->create(['role' => 'pegawai']);
    $this->pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $this->payload = fn (array $overrides = []) => array_merge([
        'jenis' => 'cuti',
        'id_cuti' => $this->cutiTahunan->id,
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-22',
        'keterangan' => 'Cuti tahunan',
    ], $overrides);
});

test('accepts cuti with id_cuti', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)())
        ->assertCreated();
});

test('rejects cuti without id_cuti', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'cuti',
        'id_cuti' => null,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('rejects an id_cuti that does not exist', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'id_cuti' => 999999,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('rejects sakit with id_cuti', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => $this->cutiTahunan->id,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('rejects dl with id_cuti', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'dl',
        'id_cuti' => $this->cutiTahunan->id,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('accepts sakit without id_cuti when the lampiran is present', function () {
    $this->post('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => null,
        'keterangan' => 'Surat dokter',
        'lampiran' => UploadedFile::fake()->create('surat.pdf', 50, 'application/pdf'),
    ]))->assertCreated();
});

test('does not require a lampiran for sakit of three working days or fewer', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-18',
        'tanggal_berakhir' => '2026-11-20',
        'keterangan' => 'Demam tiga hari',
    ]))->assertCreated();
});

test('requires a lampiran for sakit of four working days', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-17',
        'tanggal_berakhir' => '2026-11-20',
        'keterangan' => 'Surat dokter',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lampiran');
});

test('requires a lampiran for sakit that spans more than three working days', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-16',
        'tanggal_berakhir' => '2026-11-20',
        'keterangan' => 'Surat dokter',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lampiran');
});

test('requires a lampiran for cuti alasan penting', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'id_cuti' => $this->cutiPenting->id,
        'keterangan' => 'Alasan penting',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lampiran');
});

test('rejects a tanggal_berakhir before tanggal_mulai', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'tanggal_berakhir' => '2026-11-19',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tanggal_berakhir');
});

test('rejects a range that collides with a hadir presensi', function () {
    Presensi::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'tgl_presensi' => '2026-11-20',
        'status' => 'hadir',
    ]);

    $this->postJson('/test/validasi-pengajuan', ($this->payload)())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tanggal_mulai');
});

test('rejects a cuti that exceeds the remaining quota', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'id_cuti' => $this->cutiKuotaKecil->id,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tanggal_berakhir');
});

test('rejects client supplied owner, status, and approval fields', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'id_pegawai' => 999999,
        'status' => 'approved',
        'disetujui_oleh' => 1,
        'catatan' => 'Disetujui sendiri',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['id_pegawai', 'status', 'disetujui_oleh', 'catatan']);
});

test('rejects a lampiran with an unsupported extension', function () {
    $this->withHeaders(['Accept' => 'application/json'])
        ->post('/test/validasi-pengajuan', ($this->payload)([
            'jenis' => 'sakit',
            'id_cuti' => null,
            'lampiran' => UploadedFile::fake()->create('surat.txt', 50, 'text/plain'),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lampiran');
});

test('rejects a range that collides with an approved pengajuan', function () {
    Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cutiTahunan->id,
        'tanggal_mulai' => '2026-11-21',
        'tanggal_berakhir' => '2026-11-22',
        'jenis' => 'cuti',
        'status' => 'approved',
    ]);

    $this->postJson('/test/validasi-pengajuan', ($this->payload)())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('tanggal_mulai');
});

test('rejects cuti besar when it was taken within five years', function () {
    Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cutiBesar->id,
        'tanggal_mulai' => '2023-03-01',
        'tanggal_berakhir' => '2023-06-01',
        'jenis' => 'cuti',
        'status' => 'approved',
    ]);

    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'id_cuti' => $this->cutiBesar->id,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_cuti');
});

test('accepts cuti besar with the haji flag', function () {
    Pengajuan::factory()->create([
        'id_pegawai' => $this->pegawai->id,
        'id_cuti' => $this->cutiBesar->id,
        'tanggal_mulai' => '2023-03-01',
        'tanggal_berakhir' => '2023-06-01',
        'jenis' => 'cuti',
        'status' => 'approved',
    ]);

    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'id_cuti' => $this->cutiBesar->id,
        'alasan_haji' => '1',
    ]))
        ->assertCreated();
});

test('rejects the haji flag outside cuti besar', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'alasan_haji' => '1',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('alasan_haji');
});
