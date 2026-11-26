<?php

use App\Actions\Presensi\HitungMenitPulangCepatAction;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['presensi.kode_jam_kerja_diabaikan' => ['JK03']]);
});

function shiftPulangCepat(string $kode = 'JK01'): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => $kode,
        'nama' => 'Shift '.$kode,
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => '16:00:00',
    ]);
}

function presensiPulangCepat(
    Pegawai $pegawai,
    JamKerja $shift,
    ?string $jamKeluar,
    string $status = 'hadir',
    ?Pengajuan $pengajuan = null,
    string $tanggal = '2026-11-02',
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'id_pengajuan' => $pengajuan?->id,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $status === 'hadir' ? '07:30:00' : null,
        'jam_keluar' => $jamKeluar,
        'status' => $status,
    ]);
}

it('returns the number of minutes checked out before the shift ends', function (?string $jamKeluar, int $menit) {
    $pegawai = Pegawai::factory()->create();
    $presensi = presensiPulangCepat($pegawai, shiftPulangCepat(), $jamKeluar);

    expect(app(HitungMenitPulangCepatAction::class)($presensi))->toBe($menit);
})->with([
    'tepat pulang' => ['16:00:00', 0],
    'pulang satu menit lebih awal' => ['15:59:00', 1],
    'pulang tiga puluh menit lebih awal' => ['15:30:00', 30],
    'pulang setelah shift' => ['16:10:00', 0],
    'tanpa jam keluar' => [null, 0],
]);

it('ignores excluded shifts, non-hadir rows, and half-day dinas luar', function () {
    $pegawai = Pegawai::factory()->create();
    $shiftDikecualikan = shiftPulangCepat('JK03');
    $shiftNormal = shiftPulangCepat('JK01');

    $dl = Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-04',
        'tanggal_berakhir' => '2026-11-04',
        'jenis' => 'dl',
        'mode_dl' => 'setengah',
        'keterangan' => 'Dinas luar setengah hari',
        'status' => 'approved',
    ]);

    $action = app(HitungMenitPulangCepatAction::class);

    expect($action(presensiPulangCepat($pegawai, $shiftDikecualikan, '15:00:00', tanggal: '2026-11-02')))->toBe(0)
        ->and($action(presensiPulangCepat($pegawai, $shiftNormal, '15:00:00', 'cuti', tanggal: '2026-11-03')))->toBe(0)
        ->and($action(presensiPulangCepat($pegawai, $shiftNormal, '15:00:00', 'hadir', $dl, '2026-11-04')))->toBe(0);
});
