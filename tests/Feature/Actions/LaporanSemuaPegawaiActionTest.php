<?php

use App\Actions\Laporan\LaporanSemuaPegawaiAction;
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
        ->and($barisA['sel'][2])->toBe('H')
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
        ->and($barisB['h'])->toBe(0)
        ->and($barisB['a'])->toBe(21);
});

it('refuses matrix access for a user without laporan permission', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    Pegawai::factory()->create();

    expect(fn () => app(LaporanSemuaPegawaiAction::class)($user, 11, 2026))
        ->toThrow(AuthorizationException::class);
});
