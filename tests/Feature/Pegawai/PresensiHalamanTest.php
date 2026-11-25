<?php

use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
 * Halaman presensi (issue #31) menguji UI + status hari ini. Deteksi wajah
 * dan Leaflet berjalan di browser, jadi yang dikunci di sini adalah markup,
 * status server, dan endpoint dummy #32.
 */
beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(CarbonImmutable::parse('2026-11-25 09:00:00')); // Rabu, 16:00 WIB
});

/**
 * @return array{User, Pegawai}
 */
function akunHalamanPresensi(): array
{
    $user = User::factory()->create(['role' => 'pegawai']);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

function shiftHalamanPresensi(): JamKerja
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

function jadwalkanHalamanPresensi(Pegawai $pegawai, JamKerja $shift): void
{
    KonfigurasiJamKerja::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'hari' => 'Rabu',
    ]);
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatPresensiHalamanPresensi(Pegawai $pegawai, array $atribut = []): Presensi
{
    return Presensi::factory()->create(array_merge([
        'id_pegawai' => $pegawai->id,
        'tgl_presensi' => '2026-11-25',
        'status' => 'hadir',
        'jam_masuk' => '07:45:00',
        'jam_keluar' => null,
    ], $atribut));
}

function tombolAbsenNonaktif(string $html): bool
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $tombol = (new DOMXPath($dom))->query('//*[@id="tombol-absen"]')->item(0);

    return $tombol instanceof DOMElement && $tombol->hasAttribute('disabled');
}

it('renders the camera, map, and masuk state for an eligible pegawai', function () {
    [$user, $pegawai] = akunHalamanPresensi();
    jadwalkanHalamanPresensi($pegawai, shiftHalamanPresensi());

    $response = $this->actingAs($user)->get(route('presensi.index'));

    $response->assertOk()
        ->assertSee('id="halaman-presensi"', false)
        ->assertSee('data-mode="masuk"', false)
        ->assertSee('data-bisa-absen="1"', false)
        ->assertSee('data-models-url="'.asset('models').'"', false)
        ->assertSee('data-store-url="'.route('presensi.store').'"', false)
        ->assertSee('id="video-presensi"', false)
        ->assertSee('id="peta-presensi"', false)
        ->assertSee('id="tombol-absen"', false)
        ->assertSee('Belum absen')
        ->assertSee('Absen Masuk')
        ->assertSee('Shift Pagi')
        ->assertSee('07:30')
        ->assertSee('Foto dan lokasi divalidasi ulang di server sebelum disimpan.');

    expect(tombolAbsenNonaktif($response->getContent()))->toBeTrue();
});

it('switches the action to pulang after the pegawai checked in', function () {
    [$user, $pegawai] = akunHalamanPresensi();
    jadwalkanHalamanPresensi($pegawai, shiftHalamanPresensi());
    buatPresensiHalamanPresensi($pegawai);

    $response = $this->actingAs($user)->get(route('presensi.index'));

    $response->assertOk()
        ->assertSee('data-mode="keluar"', false)
        ->assertSee('Sudah masuk')
        ->assertSee('Absen Pulang')
        ->assertSee('07:45')
        ->assertSee('Jam masuk');

    expect(tombolAbsenNonaktif($response->getContent()))->toBeTrue();
});

it('disables absen and explains when there is no shift today', function () {
    [$user, $pegawai] = akunHalamanPresensi();

    $response = $this->actingAs($user)->get(route('presensi.index'));

    $response->assertOk()
        ->assertSee('data-mode="tanpa-shift"', false)
        ->assertSee('data-bisa-absen="0"', false)
        ->assertSee('Tidak ada shift terjadwal hari ini')
        ->assertSee('Absen tidak tersedia');

    expect(tombolAbsenNonaktif($response->getContent()))->toBeTrue();
});

it('shows approved leave instead of an actionable attendance form', function () {
    [$user, $pegawai] = akunHalamanPresensi();
    jadwalkanHalamanPresensi($pegawai, shiftHalamanPresensi());
    buatPresensiHalamanPresensi($pegawai, [
        'id_jam_kerja' => null,
        'status' => 'cuti',
        'jam_masuk' => null,
    ]);

    $response = $this->actingAs($user)->get(route('presensi.index'));

    $response->assertOk()
        ->assertSee('data-mode="libur"', false)
        ->assertSee('data-bisa-absen="0"', false)
        ->assertSee('Cuti')
        ->assertSee('tidak perlu melakukan absen')
        ->assertSee('Absen tidak tersedia');

    expect(tombolAbsenNonaktif($response->getContent()))->toBeTrue();
});

it('shows the completed state once jam keluar is recorded', function () {
    [$user, $pegawai] = akunHalamanPresensi();
    jadwalkanHalamanPresensi($pegawai, shiftHalamanPresensi());
    buatPresensiHalamanPresensi($pegawai, ['jam_keluar' => '16:05:00']);

    $response = $this->actingAs($user)->get(route('presensi.index'));

    $response->assertOk()
        ->assertSee('data-mode="selesai"', false)
        ->assertSee('Presensi selesai')
        ->assertSee('Presensi hari ini sudah lengkap');

    expect(tombolAbsenNonaktif($response->getContent()))->toBeTrue();
});

it('warns when the account is not linked to pegawai data', function () {
    $user = User::factory()->create(['role' => 'pegawai']);

    $this->actingAs($user)->get(route('presensi.index'))
        ->assertOk()
        ->assertSee('Data pegawai belum tersedia')
        ->assertDontSee('id="halaman-presensi"', false);
});

it('returns a structured validation error from the attendance endpoint', function () {
    [$user] = akunHalamanPresensi();

    $this->actingAs($user)
        ->postJson(route('presensi.store'))
        ->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('type', 'validasi');
});

it('protects the attendance endpoint from guests and admins', function () {
    $this->post(route('presensi.store'))->assertRedirect(route('login'));

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('presensi.store'))
        ->assertRedirect('/admin/dashboard')
        ->assertSessionHas('warning', 'Halaman ini hanya untuk akun pegawai.');
});

function dlHalamanPresensi(Pegawai $pegawai, string $mode = 'setengah'): Pengajuan
{
    return Pengajuan::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => null,
        'tanggal_mulai' => '2026-11-25',
        'tanggal_berakhir' => '2026-11-25',
        'jenis' => 'dl',
        'mode_dl' => $mode,
        'keterangan' => 'Dinas luar',
        'status' => 'approved',
    ]);
}

it('shows half day dinas luar state before and after the single check in', function () {
    [$user, $pegawai] = akunHalamanPresensi();
    $shift = shiftHalamanPresensi();
    jadwalkanHalamanPresensi($pegawai, $shift);
    $dl = dlHalamanPresensi($pegawai);

    $this->actingAs($user)->get(route('presensi.index'))
        ->assertOk()
        ->assertSee('data-mode="dl-setengah"', false)
        ->assertSee('data-bisa-absen="1"', false)
        ->assertSee('DL setengah hari')
        ->assertSee('Absen Sekali');

    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_jam_kerja' => $shift->id,
        'id_pengajuan' => $dl->id,
        'tgl_presensi' => '2026-11-25',
        'jam_masuk' => '10:00:00',
        'jam_keluar' => null,
        'status' => 'hadir',
    ]);

    $this->actingAs($user)->get(route('presensi.index'))
        ->assertOk()
        ->assertSee('data-mode="dl-setengah-selesai"', false)
        ->assertSee('data-bisa-absen="0"', false)
        ->assertSee('DL setengah selesai')
        ->assertSee('Tidak perlu absen pulang');
});
