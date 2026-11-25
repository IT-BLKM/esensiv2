<?php

use App\Models\JamKerja;
use App\Models\Pegawai;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
});

function shiftLaporan(): JamKerja
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

function pegawaiLaporan(string $nama = 'Budi Santoso', string $nip = '199302032022032001'): Pegawai
{
    $user = User::factory()->create(['role' => 'pegawai']);

    return Pegawai::factory()->create([
        'user_id' => $user->id,
        'nama' => $nama,
        'nip' => $nip,
    ]);
}

function presensiLaporan(
    Pegawai $pegawai,
    string $tanggal,
    string $status = 'hadir',
    ?JamKerja $shift = null,
    ?string $jamMasuk = '07:45:00',
    ?string $jamKeluar = '16:00:00',
): Presensi {
    return Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $status === 'hadir' ? $shift?->id : null,
        'tgl_presensi' => $tanggal,
        'jam_masuk' => $status === 'hadir' ? $jamMasuk : null,
        'jam_keluar' => $status === 'hadir' ? $jamKeluar : null,
        'status' => $status,
    ]);
}

it('redirects guests and pegawai away from admin laporan', function () {
    $this->get(route('admin.laporan.index'))->assertRedirect(route('login'));

    $pegawai = User::factory()->create(['role' => 'pegawai']);
    $this->actingAs($pegawai)->get(route('admin.laporan.index'))->assertRedirect('/dashboard');
});

it('asks the admin to choose a pegawai before showing a report', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.laporan.index'))
        ->assertOk()
        ->assertSee('Pilih pegawai pada filter di atas')
        ->assertDontSee('Daftar harian');
});

it('shows daily rows and monthly recap for the selected pegawai', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiLaporan();
    $shift = shiftLaporan();

    presensiLaporan($pegawai, '2026-11-02', shift: $shift, jamMasuk: '07:45:00', jamKeluar: '16:00:00');
    presensiLaporan($pegawai, '2026-11-03', shift: $shift, jamMasuk: '07:20:00', jamKeluar: '16:00:00');
    presensiLaporan($pegawai, '2026-11-04', status: 'cuti');
    presensiLaporan($pegawai, '2026-11-05', status: 'sakit');
    presensiLaporan($pegawai, '2026-11-06', status: 'dl');
    presensiLaporan($pegawai, '2026-10-31', shift: $shift, jamMasuk: '07:15:00');

    $this->actingAs($admin)->get(route('admin.laporan.index', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('199302032022032001')
        ->assertSee('November 2026')
        ->assertSee('Daftar harian')
        ->assertSee('Terlambat 15 menit')
        ->assertSee('Tepat waktu')
        ->assertSee('Cuti')
        ->assertSee('Sakit')
        ->assertSee('Dinas Luar')
        ->assertSee('15 menit')
        ->assertDontSee('31 Okt 2026');
});

it('shows a replacement badge when lateness was replaced', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiLaporan();
    $shift = shiftLaporan();

    presensiLaporan($pegawai, '2026-11-02', shift: $shift, jamMasuk: '07:50:00', jamKeluar: '16:22:00');

    $this->actingAs($admin)->get(route('admin.laporan.index', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 11,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('— diganti');
});

it('shows the empty state when the selected month has no attendance', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $pegawai = pegawaiLaporan();

    $this->actingAs($admin)->get(route('admin.laporan.index', [
        'id_pegawai' => $pegawai->id,
        'bulan' => 12,
        'tahun' => 2026,
    ]))
        ->assertOk()
        ->assertSee('Tidak ada presensi pada Desember 2026.');
});

it('rejects invalid laporan filters', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.laporan.index', [
            'id_pegawai' => 999999,
            'bulan' => 13,
            'tahun' => 1999,
        ]))
        ->assertSessionHasErrors(['id_pegawai', 'bulan', 'tahun']);
});

it('keeps pegawai access to the monthly report data limited to their own record', function () {
    [$userA, $pegawaiA] = [User::factory()->create(['role' => 'pegawai']), null];
    $pegawaiA = Pegawai::factory()->create(['user_id' => $userA->id]);
    $pegawaiB = pegawaiLaporan('Siti Aminah', '199401012019012002');

    $this->actingAs($userA)->get(route('pegawai.laporan.show', $pegawaiA))->assertOk();
    $this->actingAs($userA)->get(route('pegawai.laporan.show', $pegawaiB))->assertForbidden();
});

it('returns monthly report data for the pegawai own record', function () {
    $user = User::factory()->create(['role' => 'pegawai']);
    $pegawai = Pegawai::factory()->create(['user_id' => $user->id]);
    $shift = shiftLaporan();

    presensiLaporan($pegawai, '2026-11-02', shift: $shift, jamMasuk: '07:45:00');
    presensiLaporan($pegawai, '2026-10-31', shift: $shift);

    $this->actingAs($user)
        ->getJson(route('pegawai.laporan.show', [
            'pegawai' => $pegawai,
            'bulan' => 11,
            'tahun' => 2026,
        ]))
        ->assertOk()
        ->assertJsonPath('id_pegawai', $pegawai->id)
        ->assertJsonPath('bulan', 11)
        ->assertJsonPath('tahun', 2026)
        ->assertJsonPath('label_periode', 'November 2026')
        ->assertJsonPath('jumlah_presensi', 1)
        ->assertJsonPath('baris.0.tanggal', '2026-11-02')
        ->assertJsonPath('ringkasan.terlambat', 1);
});
