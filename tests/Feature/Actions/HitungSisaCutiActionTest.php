<?php

use App\Actions\Cuti\HitungSisaCutiAction;
use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\SaldoCuti;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Aturan cuti PNS (issue #37). Satu aturan satu test:
 * - 24 saat tabungan 2 tahun penuh, 12 tanpa tabungan, Y-3 diabaikan
 * - FIFO: tabungan paling lama dulu, lalu kuota tahun berjalan
 * - rollover min(sisa, maks_bawa), idempotent, tidak menimpa init ETL
 * - C02 menghanguskan kuota C01 tahun berjalan (tabungan lama tetap)
 * - C03 berdiri sendiri
 * - C04 butuh 5 tahun atau alasan haji
 */
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-15 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function cutiMaster(string $kode, string $nama, ?int $maksHari, ?int $kuota, int $maksBawa): Cuti
{
    return Cuti::factory()->create([
        'kode' => $kode,
        'nama' => $nama,
        'maks_hari' => $maksHari,
        'kuota' => $kuota,
        'maks_bawa' => $maksBawa,
    ]);
}

function tabungCuti(Pegawai $pegawai, Cuti $cuti, int $tahunSumber, int $saldo): SaldoCuti
{
    return SaldoCuti::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => $tahunSumber,
        'saldo' => $saldo,
    ]);
}

function pengajuanCuti(
    Pegawai $pegawai,
    Cuti $cuti,
    string $mulai,
    string $akhir,
    string $status = 'approved',
): Pengajuan {
    return Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tanggal_mulai' => $mulai,
        'tanggal_berakhir' => $akhir,
        'jenis' => 'cuti',
        'status' => $status,
    ]);
}

/**
 * Buat pemakaian cuti = baris presensi hari kerja dari pengajuan approved.
 * Hari dimulai dari tanggal 1 bulan tertentu agar mudah dihitung.
 */
function pakaiCuti(Pegawai $pegawai, Cuti $cuti, int $tahun, int $jumlah, int $bulan = 1): void
{
    if ($jumlah <= 0) {
        return;
    }

    $mulai = CarbonImmutable::create($tahun, $bulan, 1);
    $akhir = $mulai->addDays($jumlah - 1);

    $pengajuan = pengajuanCuti(
        $pegawai,
        $cuti,
        $mulai->toDateString(),
        $akhir->toDateString(),
    );

    foreach (range(0, $jumlah - 1) as $offset) {
        Presensi::factory()->create([
            'id_pegawai' => $pegawai->id,
            'id_jam_kerja' => null,
            'id_pengajuan' => $pengajuan->id,
            'tgl_presensi' => $mulai->addDays($offset)->toDateString(),
            'jam_masuk' => null,
            'jam_keluar' => null,
            'foto_masuk' => null,
            'foto_keluar' => null,
            'koordinat_masuk' => null,
            'koordinat_keluar' => null,
            'status' => 'cuti',
        ]);
    }
}

it('offers 24 days when both saved years are full', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    tabungCuti($pegawai, $c01, 2024, 6);
    tabungCuti($pegawai, $c01, 2025, 6);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(24)
        ->and($hasil['sisaKuota'])->toBe(12)
        ->and($hasil['maksBawaTahunDepan'])->toBe(6)
        ->and(array_column($hasil['tabungan'], 'tahun_sumber'))->toBe([2024, 2025]);
});

it('offers 12 days when there is no saved quota', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(12)
        ->and($hasil['tabungan'])->toBe([]);
});

it('offers 12 days when the previous year quota was fully used', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    pakaiCuti($pegawai, $c01, 2025, 12);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(12)
        ->and(SaldoCuti::query()->count())->toBe(0);
});

it('ignores savings older than two years', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    tabungCuti($pegawai, $c01, 2023, 6);
    tabungCuti($pegawai, $c01, 2024, 6);
    tabungCuti($pegawai, $c01, 2025, 6);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(24)
        ->and(array_column($hasil['tabungan'], 'tahun_sumber'))->toBe([2024, 2025]);
});

it('spends the oldest savings first, then the current quota', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    tabungCuti($pegawai, $c01, 2024, 6);
    tabungCuti($pegawai, $c01, 2025, 6);
    pakaiCuti($pegawai, $c01, 2026, 8);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(16)
        ->and($hasil['sisaKuota'])->toBe(12)
        ->and($hasil['tabungan'][0])->toMatchArray([
            'tahun_sumber' => 2024,
            'saldo' => 6,
            'terpakai' => 6,
            'sisa' => 0,
        ])
        ->and($hasil['tabungan'][1])->toMatchArray([
            'tahun_sumber' => 2025,
            'saldo' => 6,
            'terpakai' => 2,
            'sisa' => 4,
        ]);
});

it('reduces the current quota only after all savings are used', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    tabungCuti($pegawai, $c01, 2024, 6);
    tabungCuti($pegawai, $c01, 2025, 6);
    pakaiCuti($pegawai, $c01, 2026, 20);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(4)
        ->and($hasil['sisaKuota'])->toBe(4)
        ->and($hasil['maksBawaTahunDepan'])->toBe(4)
        ->and($hasil['tabungan'][0]['sisa'])->toBe(0)
        ->and($hasil['tabungan'][1]['sisa'])->toBe(0);
});

it('burns only the current annual quota when Cuti Bersalin is taken', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);
    $c02 = cutiMaster('C02', 'Cuti Bersalin', 90, 90, 0);

    tabungCuti($pegawai, $c01, 2024, 6);
    tabungCuti($pegawai, $c01, 2025, 6);
    pengajuanCuti($pegawai, $c02, '2026-02-01', '2026-05-01');

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['kuotaHangus'])->toBeTrue()
        ->and($hasil['sisaKuota'])->toBe(0)
        ->and($hasil['totalTersedia'])->toBe(12)
        ->and($hasil['maksBawaTahunDepan'])->toBe(0);
});

it('does not roll over a year whose annual quota was burned by Cuti Bersalin', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);
    $c02 = cutiMaster('C02', 'Cuti Bersalin', 90, 90, 0);

    pengajuanCuti($pegawai, $c02, '2025-02-01', '2025-05-01');

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(12)
        ->and(SaldoCuti::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('id_cuti', $c01->id)
            ->where('tahun_sumber', 2025)
            ->exists())->toBeFalse();
});

it('rolls over the previous year lazily and stays idempotent', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    pakaiCuti($pegawai, $c01, 2025, 5);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(18)
        ->and($hasil['tabungan'][0])->toMatchArray([
            'tahun_sumber' => 2025,
            'saldo' => 6,
        ]);

    app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect(SaldoCuti::query()
        ->where('id_pegawai', $pegawai->id)
        ->where('id_cuti', $c01->id)
        ->where('tahun_sumber', 2025)
        ->count())->toBe(1);
});

it('does not overwrite an existing rollover row from the ETL', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    tabungCuti($pegawai, $c01, 2025, 2);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(14)
        ->and(SaldoCuti::query()
            ->where('id_pegawai', $pegawai->id)
            ->where('id_cuti', $c01->id)
            ->where('tahun_sumber', 2025)
            ->value('saldo'))->toBe(2);
});

it('does not invent savings when the previous year has no activity', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);

    expect($hasil['totalTersedia'])->toBe(12)
        ->and(SaldoCuti::query()->count())->toBe(0);
});

it('keeps Cuti Alasan Penting independent from Cuti Tahunan', function () {
    $pegawai = Pegawai::factory()->create();
    $c01 = cutiMaster('C01', 'Cuti Tahunan', 12, 12, 6);
    $c03 = cutiMaster('C03', 'Cuti Alasan Penting', 30, 30, 0);

    pakaiCuti($pegawai, $c03, 2026, 5, 2);

    $tahunan = app(HitungSisaCutiAction::class)($pegawai, $c01, 2026);
    $alasanPenting = app(HitungSisaCutiAction::class)($pegawai, $c03, 2026);

    expect($tahunan['totalTersedia'])->toBe(12)
        ->and($alasanPenting['totalTersedia'])->toBe(25);
});

it('allows Cuti Besar only after five years or with a hajj reason', function () {
    $pegawai = Pegawai::factory()->create();
    $c04 = cutiMaster('C04', 'Cuti Besar', 90, 90, 0);

    pengajuanCuti($pegawai, $c04, '2023-03-01', '2023-06-01');

    $tanpaHaji = app(HitungSisaCutiAction::class)($pegawai, $c04, 2026);
    $denganHaji = app(HitungSisaCutiAction::class)($pegawai, $c04, 2026, true);

    expect($tanpaHaji['memenuhiSyarat'])->toBeFalse()
        ->and($tanpaHaji['totalTersedia'])->toBe(0)
        ->and($tanpaHaji['alasanTidakMemenuhi'])->not->toBeNull()
        ->and($denganHaji['memenuhiSyarat'])->toBeTrue()
        ->and($denganHaji['totalTersedia'])->toBe(90);
});

it('allows Cuti Besar when the last one was more than five years ago', function () {
    $pegawai = Pegawai::factory()->create();
    $c04 = cutiMaster('C04', 'Cuti Besar', 90, 90, 0);

    pengajuanCuti($pegawai, $c04, '2021-03-01', '2021-06-01');

    $hasil = app(HitungSisaCutiAction::class)($pegawai, $c04, 2026);

    expect($hasil['memenuhiSyarat'])->toBeTrue()
        ->and($hasil['totalTersedia'])->toBe(90);
});
