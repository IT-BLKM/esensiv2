<?php

use App\Actions\Laporan\LaporanSemuaPegawaiAction;
use App\Actions\Laporan\TulisExcelLaporanAction;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
    Storage::fake('local');
});

function pegawaiExportLaporan(string $nama = 'Budi Santoso', string $nip = '199302032022032001'): Pegawai
{
    $user = User::factory()->create(['role' => 'pegawai']);

    return Pegawai::factory()->create([
        'user_id' => $user->id,
        'nama' => $nama,
        'nip' => $nip,
    ]);
}

function shiftExportLaporan(): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => 'JK01',
        'nama' => 'Shift Pagi',
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => '16:00:00',
    ]);
}

function presensiExportLaporan(Pegawai $pegawai, JamKerja $shift, string $tanggal): Presensi
{
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => '07:45:00',
        'jam_keluar' => '16:00:00',
        'status' => 'hadir',
    ]);
}

it('redirects guests and pegawai away from laporan export', function () {
    $this->get(route('admin.laporan.export.excel'))->assertRedirect(route('login'));
    $this->get(route('admin.laporan.export.pdf'))->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $this->actingAs($pegawai)->get(route('admin.laporan.export.excel'))->assertRedirect('/dashboard');
    $this->actingAs($pegawai)->get(route('admin.laporan.export.pdf'))->assertRedirect('/dashboard');
});

it('downloads per-pegawai Excel with a period filename', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiExportLaporan();
    $shift = shiftExportLaporan();
    presensiExportLaporan($pegawai, $shift, '2026-11-02');

    $this->actingAs($admin)->get(route('admin.laporan.export.excel', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertDownload('laporan-presensi-budi-santoso-2026-11.xlsx')
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('downloads all-pegawai Excel with a period filename', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiExportLaporan();
    $shift = shiftExportLaporan();
    presensiExportLaporan($pegawai, $shift, '2026-11-02');

    $this->actingAs($admin)->get(route('admin.laporan.export.excel', [
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertDownload('rekap-presensi-semua-2026-11.xlsx');
});

it('downloads per-pegawai PDF with embedded photos', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiExportLaporan();
    $shift = shiftExportLaporan();

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    Storage::disk('local')->put('presensi/2026/11/02/masuk.png', $png);
    Storage::disk('local')->put('presensi/2026/11/02/keluar.png', $png);

    presensiExportLaporan($pegawai, $shift, '2026-11-02')->update([
        'foto_masuk' => 'presensi/2026/11/02/masuk.png',
        'foto_keluar' => 'presensi/2026/11/02/keluar.png',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.laporan.export.pdf', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('laporan-presensi-budi-santoso-2026-11.pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('downloads all-pegawai PDF in landscape', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiExportLaporan();
    $shift = shiftExportLaporan();
    presensiExportLaporan($pegawai, $shift, '2026-11-02');

    $response = $this->actingAs($admin)->get(route('admin.laporan.export.pdf', [
        'bulan' => 11,
        'tahun' => 2026,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('rekap-presensi-semua-2026-11.pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('rejects invalid export filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.laporan.export.excel', [
        'id_pegawai' => 999999,
        'bulan' => 13,
        'tahun' => 1999,
    ]))
        ->assertSessionHasErrors(['id_pegawai', 'bulan', 'tahun']);
});

it('marks late cells and adds a TL column in the all-pegawai Excel matrix', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftExportLaporan();
    $pegawai = pegawaiExportLaporan();
    presensiExportLaporan($pegawai, $shift, '2026-11-02');

    $data = app(LaporanSemuaPegawaiAction::class)($admin, 11, 2026);
    $path = app(TulisExcelLaporanAction::class)->semua($data);

    $reader = new Reader;
    $reader->open($path);
    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = array_map(fn ($cell) => $cell->getValue(), $row->cells);
        }
    }

    $reader->close();
    unlink($path);

    $header = collect($rows)->first(fn (array $row): bool => in_array('NIP', $row, true));
    $dataRow = collect($rows)->first(fn (array $row): bool => in_array($pegawai->nama, $row, true));

    expect($header)->toContain('TL')
        ->and($dataRow)->toContain('H!')
        ->and($dataRow)->toContain(1);
});

it('renders late markers and TL in the all-pegawai PDF view', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftExportLaporan();
    $pegawai = pegawaiExportLaporan();
    presensiExportLaporan($pegawai, $shift, '2026-11-02');

    $data = app(LaporanSemuaPegawaiAction::class)($admin, 11, 2026);

    $html = view('admin.laporan-pdf-semua', [
        'data' => $data,
        'logo' => null,
        'namaKantor' => 'Kantor Uji',
        'alamatKantor' => 'Alamat Uji',
    ])->render();

    expect($html)->toContain('TL')
        ->and($html)->toContain('H!')
        ->and($html)->toContain('hadir terlambat');
});
