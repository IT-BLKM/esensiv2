<?php

use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00', 'Asia/Jakarta'));
    config(['presensi.zona_waktu' => 'Asia/Jakarta']);
    Storage::fake('local');
});

/**
 * @return array{User, Pegawai}
 */
function akunDaftarPengajuan(): array
{
    $user = User::factory()->create(['role' => 'pegawai']);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

function cutiDaftarPengajuan(
    string $kode = 'C01',
    string $nama = 'Cuti Tahunan',
    int $kuota = 12,
): Cuti {
    return Cuti::factory()->create([
        'kode' => $kode,
        'nama' => $nama,
        'maks_hari' => $kuota,
        'kuota' => $kuota,
        'maks_bawa' => $kode === 'C01' ? 6 : 0,
    ]);
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatPengajuanDaftar(Pegawai $pegawai, ?Cuti $cuti = null, array $atribut = []): Pengajuan
{
    return Pengajuan::factory()->create(array_merge([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti?->id,
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-22',
        'jenis' => $cuti === null ? 'sakit' : 'cuti',
        'keterangan' => 'Pengajuan uji',
        'status' => 'pending',
    ], $atribut));
}

it('lists only the logged in pegawai submissions', function () {
    [$userA, $pegawaiA] = akunDaftarPengajuan();
    [, $pegawaiB] = akunDaftarPengajuan();

    $milikA = buatPengajuanDaftar($pegawaiA, cutiDaftarPengajuan('C01', 'Cuti Tahunan'));
    $milikB = buatPengajuanDaftar($pegawaiB, cutiDaftarPengajuan('C02', 'Cuti Bersalin', 90));

    $this->actingAs($userA)->get(route('pengajuan.index'))
        ->assertOk()
        ->assertSee('1 pengajuan')
        ->assertSee('Cuti Tahunan')
        ->assertDontSee('Cuti Bersalin')
        ->assertSee(route('pengajuan.show', $milikA), false)
        ->assertDontSee(route('pengajuan.show', $milikB), false);
});

it('filters submissions by status', function () {
    [$user, $pegawai] = akunDaftarPengajuan();
    $cuti = cutiDaftarPengajuan();

    buatPengajuanDaftar($pegawai, $cuti, [
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-20',
        'status' => 'pending',
    ]);
    buatPengajuanDaftar($pegawai, $cuti, [
        'tanggal_mulai' => '2026-11-23',
        'tanggal_berakhir' => '2026-11-23',
        'status' => 'approved',
    ]);

    $this->actingAs($user)->get(route('pengajuan.index', ['status' => 'approved']))
        ->assertOk()
        ->assertSee('1 pengajuan')
        ->assertSee('23 Nov 2026')
        ->assertDontSee('20 Nov 2026');
});

it('filters submissions by month and year', function () {
    [$user, $pegawai] = akunDaftarPengajuan();
    $cuti = cutiDaftarPengajuan();

    buatPengajuanDaftar($pegawai, $cuti, [
        'tanggal_mulai' => '2026-10-05',
        'tanggal_berakhir' => '2026-10-05',
    ]);
    buatPengajuanDaftar($pegawai, $cuti, [
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-20',
    ]);

    $this->actingAs($user)->get(route('pengajuan.index', ['bulan' => 10, 'tahun' => 2026]))
        ->assertOk()
        ->assertSee('1 pengajuan')
        ->assertSee('05 Okt 2026')
        ->assertDontSee('20 Nov 2026');
});

it('shows an empty state when there is no submission', function () {
    [$user] = akunDaftarPengajuan();

    $this->actingAs($user)->get(route('pengajuan.index'))
        ->assertOk()
        ->assertSee('0 pengajuan')
        ->assertSee('Belum ada pengajuan yang cocok dengan filter ini.');
});

it('shows the detail with decision notes and a private attachment link', function () {
    [$user, $pegawai] = akunDaftarPengajuan();
    $admin = User::factory()->create(['role' => 'admin', 'nama' => 'Admin Penilai']);
    $cuti = cutiDaftarPengajuan();

    $pengajuan = buatPengajuanDaftar($pegawai, $cuti, [
        'keterangan' => 'Keperluan keluarga',
        'status' => 'approved',
        'lampiran' => 'lampiran-pengajuan/surat.pdf',
        'disetujui_oleh' => $admin->id,
        'disetujui_pada' => '2026-11-24 10:15:00',
        'catatan' => 'Disetujui, selamat berlibur.',
    ]);

    Storage::disk('local')->put('lampiran-pengajuan/surat.pdf', 'isi-pdf');

    $this->actingAs($user)->get(route('pengajuan.show', $pengajuan))
        ->assertOk()
        ->assertSee('Detail pengajuan')
        ->assertSee('Cuti Tahunan')
        ->assertSee('Keperluan keluarga')
        ->assertSee('Disetujui, selamat berlibur.')
        ->assertSee('Admin Penilai')
        ->assertSee('24 Nov 2026 10:15')
        ->assertSee(route('pengajuan.lampiran', $pengajuan), false)
        ->assertSee('Unduh lampiran');
});

it('forbids opening another pegawai submission', function () {
    [$userA] = akunDaftarPengajuan();
    [, $pegawaiB] = akunDaftarPengajuan();

    $milikB = buatPengajuanDaftar($pegawaiB);

    $this->actingAs($userA)->get(route('pengajuan.show', $milikB))
        ->assertForbidden();

    $this->actingAs($userA)->get(route('pengajuan.lampiran', $milikB))
        ->assertForbidden();
});

it('downloads the attachment privately for its owner', function () {
    [$user, $pegawai] = akunDaftarPengajuan();
    $pengajuan = buatPengajuanDaftar($pegawai, cutiDaftarPengajuan(), [
        'lampiran' => 'lampiran-pengajuan/surat.pdf',
    ]);

    Storage::disk('local')->put('lampiran-pengajuan/surat.pdf', 'isi-pdf');

    $this->actingAs($user)->get(route('pengajuan.lampiran', $pengajuan))
        ->assertOk()
        ->assertDownload('lampiran-pengajuan-'.$pengajuan->id.'.pdf');
});

it('returns 404 when the attachment file is missing', function () {
    [$user, $pegawai] = akunDaftarPengajuan();
    $pengajuan = buatPengajuanDaftar($pegawai, cutiDaftarPengajuan(), [
        'lampiran' => 'lampiran-pengajuan/hilang.pdf',
    ]);

    $this->actingAs($user)->get(route('pengajuan.lampiran', $pengajuan))
        ->assertNotFound();
});

it('rejects malformed filter values', function (string $field, string $nilai) {
    [$user] = akunDaftarPengajuan();

    $this->actingAs($user)
        ->getJson(route('pengajuan.index', [$field => $nilai]))
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'status tidak dikenal' => ['status', 'dibatalkan'],
    'bulan di luar rentang' => ['bulan', '13'],
    'tahun bukan angka' => ['tahun', 'dua ribu'],
]);

it('sends a guest to the login page', function () {
    $pengajuan = buatPengajuanDaftar(akunDaftarPengajuan()[1]);

    $this->get(route('pengajuan.index'))->assertRedirect(route('login'));
    $this->get(route('pengajuan.show', $pengajuan))->assertRedirect(route('login'));
    $this->get(route('pengajuan.lampiran', $pengajuan))->assertRedirect(route('login'));
});

it('warns when the account is not linked to pegawai data', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->get(route('pengajuan.index'))
        ->assertOk()
        ->assertSee('Data pegawai belum tersedia')
        ->assertDontSee('Filter');
});
