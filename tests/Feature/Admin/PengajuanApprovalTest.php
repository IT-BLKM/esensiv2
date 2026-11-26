<?php

use App\Actions\Pengajuan\ApprovePengajuanAction;
use App\Models\Cuti;
use App\Models\HariLibur;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\SaldoCuti;
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

function adminPengajuanApproval(): User
{
    return User::factory()->create(['role' => 'admin', 'nama' => 'Admin Approver']);
}

/**
 * @return array{User, Pegawai}
 */
function akunPengajuanApproval(): array
{
    $user = User::factory()->create(['role' => 'pegawai']);

    return [$user, Pegawai::factory()->create(['user_id' => $user->id])];
}

function cutiPengajuanApproval(string $kode = 'C01'): Cuti
{
    return Cuti::factory()->create([
        'kode' => $kode,
        'nama' => $kode === 'C01' ? 'Cuti Tahunan' : 'Cuti '.$kode,
        'maks_hari' => 12,
        'kuota' => 12,
        'maks_bawa' => $kode === 'C01' ? 6 : 0,
    ]);
}

function shiftPengajuanApproval(): JamKerja
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

function jadwalkanPengajuanApproval(Pegawai $pegawai, JamKerja $shift): void
{
    foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
        KonfigurasiJamKerja::factory()->create([
            'id_pegawai' => $pegawai->id,
            'id_jam_kerja' => $shift->id,
            'hari' => $hari,
        ]);
    }
}

/**
 * @param  array<string, mixed>  $atribut
 */
function buatPengajuanApproval(Pegawai $pegawai, Cuti $cuti, array $atribut = []): Pengajuan
{
    return Pengajuan::factory()->create(array_merge([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tanggal_mulai' => '2026-11-20', // Jumat
        'tanggal_berakhir' => '2026-11-23', // Senin
        'jenis' => 'cuti',
        'keterangan' => 'Keperluan keluarga',
        'status' => 'pending',
        'disetujui_oleh' => null,
        'disetujui_pada' => null,
        'catatan' => null,
    ], $atribut));
}

it('approves within one transaction and creates presensi only for working days', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();
    $shift = shiftPengajuanApproval();

    jadwalkanPengajuanApproval($pegawai, $shift);

    $saldo = SaldoCuti::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2025,
        'saldo' => 6,
    ]);

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan), [
            'catatan_setujui' => 'Disetujui, hati-hati di jalan.',
        ])
        ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
        ->assertSessionHas('success');

    $pengajuan->refresh();

    expect($pengajuan->status)->toBe('approved')
        ->and($pengajuan->disetujui_oleh)->toBe($admin->id)
        ->and($pengajuan->disetujui_pada)->toBe('2026-11-25 09:00:00')
        ->and($pengajuan->catatan)->toBe('Disetujui, hati-hati di jalan.')
        ->and($saldo->fresh()->saldo)->toBe(6);

    $presensi = Presensi::query()
        ->where('id_pengajuan', $pengajuan->id)
        ->orderBy('tgl_presensi')
        ->get();

    expect($presensi)->toHaveCount(2)
        ->and($presensi->pluck('tgl_presensi')->all())->toBe(['2026-11-20', '2026-11-23'])
        ->and($presensi->pluck('status')->unique()->all())->toBe(['cuti'])
        ->and($presensi->pluck('id_jam_kerja')->unique()->all())->toBe([$shift->id]);
});

it('does not create presensi on a national holiday', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    HariLibur::factory()->create([
        'tanggal' => '2026-11-23',
        'nama' => 'Hari libur uji',
    ]);

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertRedirect();

    $presensi = Presensi::query()
        ->where('id_pengajuan', $pengajuan->id)
        ->orderBy('tgl_presensi')
        ->get();

    expect($presensi)->toHaveCount(1)
        ->and($presensi->first()->tgl_presensi)->toBe('2026-11-20');
});

it('falls back to Monday-Friday when the pegawai has no shift configuration', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertRedirect();

    $presensi = Presensi::query()->where('id_pengajuan', $pengajuan->id)->get();

    expect($presensi)->toHaveCount(2)
        ->and($presensi->pluck('id_jam_kerja')->unique()->all())->toBe([null]);
});

it('does not create presensi rows for a weekend-only range', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    jadwalkanPengajuanApproval($pegawai, shiftPengajuanApproval());

    $pengajuan = buatPengajuanApproval($pegawai, $cuti, [
        'tanggal_mulai' => '2026-11-21', // Sabtu
        'tanggal_berakhir' => '2026-11-22', // Minggu
    ]);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertRedirect();

    expect($pengajuan->fresh()->status)->toBe('approved')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});

it('rejects the whole approval when a hadir presensi already exists', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'tgl_presensi' => '2026-11-20',
        'status' => 'hadir',
    ]);

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);

    $this->from(route('admin.pengajuan.show', $pengajuan))
        ->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
        ->assertSessionHasErrors('pengajuan');

    expect($pengajuan->fresh()->status)->toBe('pending')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});

it('rejects the whole approval when another approved pengajuan overlaps', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    buatPengajuanApproval($pegawai, $cuti, [
        'tanggal_mulai' => '2026-11-22',
        'tanggal_berakhir' => '2026-11-23',
        'status' => 'approved',
        'keterangan' => 'Sudah disetujui',
    ]);

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);

    $this->from(route('admin.pengajuan.show', $pengajuan))
        ->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertSessionHasErrors('pengajuan');

    expect($pengajuan->fresh()->status)->toBe('pending')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});

it('refuses to approve an already decided pengajuan', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $pengajuan = buatPengajuanApproval($pegawai, $cuti, ['status' => 'approved']);

    $this->from(route('admin.pengajuan.show', $pengajuan))
        ->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertSessionHasErrors('pengajuan');

    expect($pengajuan->fresh()->status)->toBe('approved')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});

it('rejects a pending pengajuan without creating presensi', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.reject', $pengajuan), [
            'catatan_tolak' => 'Kuota tidak mencukupi.',
        ])
        ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
        ->assertSessionHas('success');

    $pengajuan->refresh();

    expect($pengajuan->status)->toBe('rejected')
        ->and($pengajuan->disetujui_oleh)->toBe($admin->id)
        ->and($pengajuan->disetujui_pada)->toBe('2026-11-25 09:00:00')
        ->and($pengajuan->catatan)->toBe('Kuota tidak mencukupi.')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});

it('requires a note when rejecting', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);

    $this->from(route('admin.pengajuan.show', $pengajuan))
        ->actingAs($admin)
        ->post(route('admin.pengajuan.reject', $pengajuan))
        ->assertSessionHasErrors('catatan_tolak');

    expect($pengajuan->fresh()->status)->toBe('pending');
});

it('rolls back the status update when presensi creation fails', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $pengajuan = buatPengajuanApproval($pegawai, cutiPengajuanApproval());

    Presensi::creating(function (): void {
        throw new RuntimeException('gagal uji di tengah transaksi');
    });

    try {
        app(ApprovePengajuanAction::class)($admin, $pengajuan);
        $this->fail('Approval seharusnya melempar exception.');
    } catch (RuntimeException $error) {
        expect($error->getMessage())->toBe('gagal uji di tengah transaksi');
    } finally {
        Presensi::flushEventListeners();
    }

    expect($pengajuan->fresh()->status)->toBe('pending')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});

it('lists pending submissions by default and supports filters', function () {
    $admin = adminPengajuanApproval();
    [, $pegawaiA] = akunPengajuanApproval();
    [, $pegawaiB] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    buatPengajuanApproval($pegawaiA, $cuti, [
        'tanggal_mulai' => '2026-11-20',
        'tanggal_berakhir' => '2026-11-20',
        'status' => 'pending',
    ]);
    buatPengajuanApproval($pegawaiA, $cuti, [
        'tanggal_mulai' => '2026-11-21',
        'tanggal_berakhir' => '2026-11-21',
        'status' => 'approved',
    ]);
    buatPengajuanApproval($pegawaiB, $cuti, [
        'tanggal_mulai' => '2026-11-22',
        'tanggal_berakhir' => '2026-11-22',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)->get(route('admin.pengajuan.index'))
        ->assertOk()
        ->assertSee('2 pengajuan')
        ->assertSee($pegawaiA->nama)
        ->assertSee($pegawaiB->nama);

    $this->actingAs($admin)->get(route('admin.pengajuan.index', ['status' => 'approved']))
        ->assertOk()
        ->assertSee('1 pengajuan');

    $this->actingAs($admin)->get(route('admin.pengajuan.index', ['id_pegawai' => $pegawaiB->id, 'status' => 'pending']))
        ->assertOk()
        ->assertSee('1 pengajuan')
        ->assertSee('22 Nov 2026')
        ->assertDontSee('20 Nov 2026');

    $this->actingAs($admin)->get(route('admin.pengajuan.index', ['status' => '']))
        ->assertOk()
        ->assertSee('3 pengajuan');

    $this->actingAs($admin)->get(route('admin.pengajuan.index', [
        'status' => '',
        'tanggal_dari' => '2026-11-22',
        'tanggal_sampai' => '2026-11-22',
    ]))
        ->assertOk()
        ->assertSee('1 pengajuan')
        ->assertSee('22 Nov 2026')
        ->assertDontSee('20 Nov 2026');
});

it('shows the working day report and decision forms on the detail page', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    jadwalkanPengajuanApproval($pegawai, shiftPengajuanApproval());

    $pengajuan = buatPengajuanApproval($pegawai, $cuti, [
        'lampiran' => 'lampiran-pengajuan/surat.pdf',
    ]);

    Storage::disk('local')->put('lampiran-pengajuan/surat.pdf', 'isi-pdf');

    $this->actingAs($admin)->get(route('admin.pengajuan.show', $pengajuan))
        ->assertOk()
        ->assertSee('2 hari')
        ->assertSee('20 Nov 2026')
        ->assertSee('23 Nov 2026')
        ->assertSee('Setujui pengajuan')
        ->assertSee('Tolak pengajuan')
        ->assertSee(route('admin.pengajuan.lampiran', $pengajuan), false);
});

it('lets admin download a private attachment', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $pengajuan = buatPengajuanApproval($pegawai, $cuti, [
        'lampiran' => 'lampiran-pengajuan/surat.pdf',
    ]);

    Storage::disk('local')->put('lampiran-pengajuan/surat.pdf', 'isi-pdf');

    $this->actingAs($admin)->get(route('admin.pengajuan.lampiran', $pengajuan))
        ->assertOk()
        ->assertDownload('lampiran-pengajuan-'.$pengajuan->id.'.pdf');
});

it('protects the admin panel from guests and pegawai', function () {
    $this->get(route('admin.pengajuan.index'))->assertRedirect(route('login'));

    [$pegawaiUser] = akunPengajuanApproval();

    $this->actingAs($pegawaiUser)->get(route('admin.pengajuan.index'))
        ->assertRedirect('/dashboard')
        ->assertSessionHas('warning', 'Halaman ini hanya untuk akun admin.');
});

it('rejects malformed admin filters', function () {
    $admin = adminPengajuanApproval();

    $this->actingAs($admin)
        ->getJson(route('admin.pengajuan.index', ['id_pegawai' => 999999]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('id_pegawai');

    $this->actingAs($admin)
        ->getJson(route('admin.pengajuan.index', ['status' => 'dibatalkan']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('cancels an approved pengajuan and restores the state before approval', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    jadwalkanPengajuanApproval($pegawai, shiftPengajuanApproval());

    $saldo = SaldoCuti::factory()->create([
        'id_pegawai' => $pegawai->id,
        'id_cuti' => $cuti->id,
        'tahun_sumber' => 2025,
        'saldo' => 6,
    ]);

    // Baris di luar rentang cuti tidak boleh ikut terhapus.
    $presensiLain = Presensi::factory()->create([
        'id_pegawai' => $pegawai->id,
        'tgl_presensi' => '2026-12-01',
        'status' => 'hadir',
    ]);

    $pengajuan = buatPengajuanApproval($pegawai, $cuti);
    $sebelumApprove = $pengajuan->only(['status', 'disetujui_oleh', 'disetujui_pada', 'catatan']);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan), ['catatan_setujui' => 'Disetujui.'])
        ->assertRedirect();

    expect(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(2);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.cancel', $pengajuan))
        ->assertRedirect(route('admin.pengajuan.show', $pengajuan))
        ->assertSessionHas('success');

    $pengajuan->refresh();

    expect($pengajuan->only(['status', 'disetujui_oleh', 'disetujui_pada', 'catatan']))->toBe($sebelumApprove)
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0)
        ->and($presensiLain->fresh())->not->toBeNull()
        ->and($saldo->fresh()->saldo)->toBe(6);
});

it('refuses to cancel a pending or rejected pengajuan', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $pending = buatPengajuanApproval($pegawai, $cuti);

    $this->from(route('admin.pengajuan.show', $pending))
        ->actingAs($admin)
        ->post(route('admin.pengajuan.cancel', $pending))
        ->assertSessionHasErrors('pengajuan');

    $rejected = buatPengajuanApproval($pegawai, $cuti, [
        'tanggal_mulai' => '2026-11-27',
        'tanggal_berakhir' => '2026-11-27',
        'status' => 'rejected',
    ]);

    $this->from(route('admin.pengajuan.show', $rejected))
        ->actingAs($admin)
        ->post(route('admin.pengajuan.cancel', $rejected))
        ->assertSessionHasErrors('pengajuan');

    expect($pending->fresh()->status)->toBe('pending')
        ->and($rejected->fresh()->status)->toBe('rejected');
});

it('does not let a pegawai cancel an approval', function () {
    [$pegawaiUser, $pegawai] = akunPengajuanApproval();
    $pengajuan = buatPengajuanApproval($pegawai, cutiPengajuanApproval(), ['status' => 'approved']);

    $this->actingAs($pegawaiUser)
        ->post(route('admin.pengajuan.cancel', $pengajuan))
        ->assertRedirect('/dashboard')
        ->assertSessionHas('warning', 'Halaman ini hanya untuk akun admin.');

    expect($pengajuan->fresh()->status)->toBe('approved');
});

it('shows the cancel action only for an approved pengajuan', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $approved = buatPengajuanApproval($pegawai, $cuti, ['status' => 'approved']);
    $pending = buatPengajuanApproval($pegawai, $cuti, [
        'tanggal_mulai' => '2026-11-27',
        'tanggal_berakhir' => '2026-11-27',
    ]);

    $this->actingAs($admin)->get(route('admin.pengajuan.show', $approved))
        ->assertOk()
        ->assertSee('Batalkan persetujuan');

    $this->actingAs($admin)->get(route('admin.pengajuan.show', $pending))
        ->assertOk()
        ->assertDontSee('Batalkan persetujuan');
});

it('creates dl rows for a full day dinas luar approval', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();
    $shift = shiftPengajuanApproval();

    jadwalkanPengajuanApproval($pegawai, $shift);

    $pengajuan = buatPengajuanApproval($pegawai, $cuti, [
        'id_cuti' => null,
        'jenis' => 'dl',
        'mode_dl' => 'full',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertRedirect();

    $presensi = Presensi::query()->where('id_pengajuan', $pengajuan->id)->orderBy('tgl_presensi')->get();

    expect($presensi)->toHaveCount(2)
        ->and($presensi->pluck('tgl_presensi')->all())->toBe(['2026-11-20', '2026-11-23'])
        ->and($presensi->pluck('status')->unique()->all())->toBe(['dl'])
        ->and($presensi->pluck('id_jam_kerja')->unique()->all())->toBe([$shift->id]);
});

it('does not create presensi rows for a half day dinas luar approval', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    $pengajuan = buatPengajuanApproval($pegawai, $cuti, [
        'id_cuti' => null,
        'jenis' => 'dl',
        'mode_dl' => 'setengah',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($pengajuan->fresh()->status)->toBe('approved')
        ->and(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(0);
});

it('treats legacy dinas luar without mode as a full day', function () {
    $admin = adminPengajuanApproval();
    [, $pegawai] = akunPengajuanApproval();
    $cuti = cutiPengajuanApproval();

    jadwalkanPengajuanApproval($pegawai, shiftPengajuanApproval());

    $pengajuan = buatPengajuanApproval($pegawai, $cuti, [
        'id_cuti' => null,
        'jenis' => 'dl',
        'mode_dl' => null,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.pengajuan.approve', $pengajuan))
        ->assertRedirect();

    expect(Presensi::query()->where('id_pengajuan', $pengajuan->id)->count())->toBe(2);
});
