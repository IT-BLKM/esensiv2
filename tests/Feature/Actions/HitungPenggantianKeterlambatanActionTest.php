<?php

use App\Actions\Presensi\HitungPenggantianKeterlambatanAction;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\PenggantianKeterlambatan;
use App\Models\Presensi;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'presensi.zona_waktu' => 'Asia/Jakarta',
        'presensi.kode_jam_kerja_diabaikan' => ['JK03'],
    ]);
});

function shiftGanti(string $jamPulang = '16:00:00', string $kode = 'JK01'): JamKerja
{
    return JamKerja::factory()->create([
        'kode' => $kode,
        'nama' => 'Shift '.$kode,
        'awal_masuk' => '07:00:00',
        'jam_masuk' => '07:30:00',
        'akhir_masuk' => '09:30:00',
        'jam_pulang' => $jamPulang,
    ]);
}

function presensiGanti(
    Pegawai $pegawai,
    JamKerja $shift,
    string $jamMasuk = '07:50:00',
    ?string $jamKeluar = '16:20:00',
    string $tanggal = '2026-11-25',
    ?Pengajuan $pengajuan = null,
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'id_pengajuan' => $pengajuan?->id,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $jamMasuk,
        'jam_keluar' => $jamKeluar,
        'status' => 'hadir',
    ]);
}

it('accepts checkout inside the replacement window', function (string $jamKeluar, bool $terpenuhi, ?string $target, ?string $batas) {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftGanti();
    $presensi = presensiGanti($pegawai, $shift, '07:50:00', $jamKeluar);

    $hasil = app(HitungPenggantianKeterlambatanAction::class)($presensi, $shift);

    expect($hasil)->not->toBeNull()
        ->and($hasil['menitTerlambat'])->toBe(20)
        ->and($hasil['targetPulang'])->toBe($target)
        ->and($hasil['batasPulang'])->toBe($batas)
        ->and($hasil['terpenuhi'])->toBe($terpenuhi);
})->with([
    'tepat di target' => ['16:20:00', true, '16:20:00', '16:25:00'],
    'tepat di batas' => ['16:25:00', true, '16:20:00', '16:25:00'],
    'sebelum target' => ['16:19:00', false, '16:20:00', '16:25:00'],
    'melewati batas' => ['16:26:00', false, '16:20:00', '16:25:00'],
    'pulang normal' => ['16:00:00', false, '16:20:00', '16:25:00'],
]);

it('uses shift end plus lateness for the target and adds five minutes', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftGanti('16:30:00');
    $presensi = presensiGanti($pegawai, $shift, '07:50:00', '16:50:00');

    $hasil = app(HitungPenggantianKeterlambatanAction::class)($presensi, $shift);

    expect($hasil['targetPulang'])->toBe('16:50:00')
        ->and($hasil['batasPulang'])->toBe('16:55:00')
        ->and($hasil['terpenuhi'])->toBeTrue();
});

it('does not allow replacement for thirty-one minutes of lateness', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftGanti();
    $presensi = presensiGanti($pegawai, $shift, '08:01:00', '16:31:00');

    $hasil = app(HitungPenggantianKeterlambatanAction::class)($presensi, $shift);

    expect($hasil['menitTerlambat'])->toBe(31)
        ->and($hasil['bisaDiganti'])->toBeFalse()
        ->and($hasil['targetPulang'])->toBeNull()
        ->and($hasil['batasPulang'])->toBeNull()
        ->and($hasil['terpenuhi'])->toBeFalse();
});

it('does not decide replacement before checkout', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftGanti();
    $presensi = presensiGanti($pegawai, $shift, '07:50:00', null);

    $hasil = app(HitungPenggantianKeterlambatanAction::class)($presensi, $shift);

    expect($hasil['terpenuhi'])->toBeFalse()
        ->and($hasil['jamSelesai'])->toBeNull()
        ->and($hasil['targetPulang'])->toBe('16:20:00')
        ->and($hasil['batasPulang'])->toBe('16:25:00');
});

it('ignores on-time rows, excluded shifts, and half-day dinas luar', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftGanti();
    $shiftDikecualikan = shiftGanti('16:00:00', 'JK03');

    $tepatWaktu = presensiGanti($pegawai, $shift, '07:30:00', '16:00:00', '2026-11-25');
    $dikecualikan = presensiGanti($pegawai, $shiftDikecualikan, '08:00:00', '16:00:00', '2026-11-26');

    $dl = Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-27',
        'tanggal_berakhir' => '2026-11-27',
        'jenis' => 'dl',
        'mode_dl' => 'setengah',
        'keterangan' => 'DL setengah',
        'status' => 'approved',
    ]);
    $dlSetengah = presensiGanti($pegawai, $shift, '10:00:00', null, '2026-11-27', $dl);

    $action = app(HitungPenggantianKeterlambatanAction::class);

    expect($action($tepatWaktu, $shift))->toBeNull()
        ->and($action($dikecualikan, $shiftDikecualikan))->toBeNull()
        ->and($action($dlSetengah, $shift))->toBeNull();
});

it('uses the saved audit snapshot when present', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftGanti();
    $presensi = presensiGanti($pegawai, $shift, '07:50:00', '16:22:00');

    PenggantianKeterlambatan::create([
        'id_presensi' => $presensi->id,
        'tanggal' => '2026-11-25',
        'jam_mulai' => '16:00:00',
        'jam_selesai' => '16:22:00',
        'target_pulang' => '16:20:00',
        'batas_pulang' => '16:25:00',
        'menit_terlambat' => 20,
        'menit_diganti' => 22,
        'bisa_diganti' => true,
        'terpenuhi' => true,
        'catatan' => 'Snapshot audit',
    ]);

    $hasil = app(HitungPenggantianKeterlambatanAction::class)($presensi->fresh(), $shift);

    expect($hasil['catatan'])->toBe('Snapshot audit')
        ->and($hasil['terpenuhi'])->toBeTrue()
        ->and($hasil['menitDiganti'])->toBe(22);
});

it('does not require a database snapshot for historical rows', function () {
    $pegawai = Pegawai::factory()->create();
    $shift = shiftGanti();
    $presensi = presensiGanti($pegawai, $shift, '07:50:00', '16:21:00');

    $hasil = app(HitungPenggantianKeterlambatanAction::class)(
        $presensi,
        $shift,
    );

    expect(PenggantianKeterlambatan::count())->toBe(0)
        ->and($hasil['terpenuhi'])->toBeTrue()
        ->and($hasil['menitDiganti'])->toBe(21);
});
