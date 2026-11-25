<?php

use App\Http\Requests\StorePengajuanRequest;
use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
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
        'kuota' => 12,
    ]);
    $this->cutiPenting = Cuti::factory()->create([
        'kode' => 'C03',
        'nama' => 'Cuti Alasan Penting',
        'kuota' => null,
    ]);
    $this->cutiKuotaKecil = Cuti::factory()->create([
        'kode' => 'C05',
        'nama' => 'Cuti Kuota Kecil',
        'kuota' => 0,
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

test('requires a lampiran for sakit', function () {
    $this->postJson('/test/validasi-pengajuan', ($this->payload)([
        'jenis' => 'sakit',
        'id_cuti' => null,
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
