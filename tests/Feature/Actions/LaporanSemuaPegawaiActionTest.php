<?php

use App\Actions\Laporan\HitungPotonganTunjanganKinerjaAction;
use App\Actions\Laporan\LaporanSemuaPegawaiAction;
use App\Models\HariLibur;
use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function shiftMatriksLaporan(): JamKerja
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

it('builds the H/DL/C/S/A matrix for every pegawai', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftMatriksLaporan();
    $pegawaiA = Pegawai::factory()->create(['nip' => '199302032022032001', 'nama' => 'Budi Santoso']);
    $pegawaiB = Pegawai::factory()->create(['nip' => '199401012019012002', 'nama' => 'Siti Aminah']);

    foreach ([
        ['tanggal' => '2026-11-02', 'status' => 'hadir'],
        ['tanggal' => '2026-11-03', 'status' => 'cuti'],
        ['tanggal' => '2026-11-04', 'status' => 'dl'],
        ['tanggal' => '2026-11-05', 'status' => 'sakit'],
    ] as $data) {
        Presensi::factory()->create([
            'id_pegawai' => $pegawaiA->id,
            'id_jam_kerja' => $data['status'] === 'hadir' ? $shift->id : null,
            'tgl_presensi' => $data['tanggal'],
            'jam_masuk' => $data['status'] === 'hadir' ? '07:45:00' : null,
            'jam_keluar' => $data['status'] === 'hadir' ? '16:00:00' : null,
            'status' => $data['status'],
        ]);
    }

    $hasil = app(LaporanSemuaPegawaiAction::class)($admin, 11, 2026);
    $barisA = collect($hasil['baris'])->firstWhere('id', $pegawaiA->id);
    $barisB = collect($hasil['baris'])->firstWhere('id', $pegawaiB->id);

    expect($hasil['hari'])->toBe(range(1, 30))
        ->and($barisA['sel'][1])->toBeNull()   // Minggu
        ->and($barisA['sel'][2])->toBe('TL1')
        ->and($barisA['sel'][3])->toBe('C')
        ->and($barisA['sel'][4])->toBe('DL')
        ->and($barisA['sel'][5])->toBe('S')
        ->and($barisA['sel'][6])->toBe('A')
        ->and($barisA['sel'][7])->toBeNull()   // Sabtu
        ->and($barisA['h'])->toBe(1)
        ->and($barisA['dl'])->toBe(1)
        ->and($barisA['c'])->toBe(1)
        ->and($barisA['s'])->toBe(1)
        ->and($barisA['a'])->toBe(17)          // 21 hari kerja - 4 baris
        ->and($barisA['tl'])->toBe(1)
        ->and($barisA['psw'])->toBe(0)
        ->and($barisB['h'])->toBe(0)
        ->and($barisB['a'])->toBe(21)
        ->and($hasil['akhirPekan'])->toBe([1, 7, 8, 14, 15, 21, 22, 28, 29]);
});

it('counts pulang sebelum waktu in the PSW column', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftMatriksLaporan();
    $pegawai = Pegawai::factory()->create(['nip' => '199302032022032001', 'nama' => 'Budi Santoso']);

    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => '2026-11-02',
        'jam_masuk' => '07:30:00',
        'jam_keluar' => '15:30:00',
        'status' => 'hadir',
    ]);

    $hasil = app(LaporanSemuaPegawaiAction::class)($admin, 11, 2026);
    $baris = collect($hasil['baris'])->firstWhere('id', $pegawai->id);

    expect($baris['sel'][2])->toBe('PSW1')
        ->and($baris['h'])->toBe(1)
        ->and($baris['tl'])->toBe(0)
        ->and($baris['psw'])->toBe(1);
});

it('combines TL and PSW levels in one day cell', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftMatriksLaporan();
    $pegawai = Pegawai::factory()->create(['nip' => '199302032022032001', 'nama' => 'Budi Santoso']);

    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => '2026-11-02',
        'jam_masuk' => '07:45:00',
        'jam_keluar' => '15:30:00',
        'status' => 'hadir',
    ]);

    $hasil = app(LaporanSemuaPegawaiAction::class)($admin, 11, 2026);
    $baris = collect($hasil['baris'])->firstWhere('id', $pegawai->id);

    expect($baris['sel'][2])->toBe("TL1\nPSW1")
        ->and($baris['tl'])->toBe(1)
        ->and($baris['psw'])->toBe(1);
});

it('keeps replaced lateness as TL in the matrix while tukin removes its deduction', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $shift = shiftMatriksLaporan();
    $pegawai = Pegawai::factory()->create(['nip' => '199302032022032001', 'nama' => 'Budi Santoso']);

    // Masuk 07:50 (telat 20 menit), pulang 16:20 = jendela penggantian 16:20–16:25.
    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'tgl_presensi' => '2026-11-02',
        'jam_masuk' => '07:50:00',
        'jam_keluar' => '16:20:00',
        'status' => 'hadir',
    ]);

    $matriks = app(LaporanSemuaPegawaiAction::class)($admin, 11, 2026);
    $baris = collect($matriks['baris'])->firstWhere('id', $pegawai->id);

    $tukin = app(HitungPotonganTunjanganKinerjaAction::class)->bulanan($pegawai, 2026, 11);

    expect($baris['sel'][2])->toBe('H!')
        ->and($baris['tl'])->toBe(1)
        ->and($tukin['ringkasan']['jumlahPenggantian'])->toBe(1)
        ->and($tukin['ringkasan']['totalPersenTl'])->toBe(0.0);
});

it('does not mark a national holiday as alpa', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = Pegawai::factory()->create(['nip' => '199302032022032001', 'nama' => 'Budi Santoso']);

    HariLibur::factory()->create([
        'tanggal' => '2026-11-25',
        'nama' => 'Hari libur uji',
    ]);

    $hasil = app(LaporanSemuaPegawaiAction::class)($admin, 11, 2026);
    $baris = collect($hasil['baris'])->firstWhere('id', $pegawai->id);

    expect($baris['sel'][25])->toBeNull()
        ->and($baris['a'])->toBe(20)
        ->and($hasil['hariLibur'])->toContain(25);
});

it('refuses matrix access for a user without laporan permission', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    Pegawai::factory()->create();

    expect(fn () => app(LaporanSemuaPegawaiAction::class)($user, 11, 2026))
        ->toThrow(AuthorizationException::class);
});
