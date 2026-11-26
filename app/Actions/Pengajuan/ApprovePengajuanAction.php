<?php

namespace App\Actions\Pengajuan;

use App\Models\Pengajuan;
use App\Models\Presensi;
use App\Models\TukinPeriode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Setujui pengajuan dalam satu transaksi (issue #39):
 * 1. kunci baris pengajuan dan pastikan masih pending;
 * 2. tolak utuh bila ada presensi di rentang atau pengajuan approved lain
 *    yang tumpang tindih;
 * 3. buat baris presensi HANYA untuk hari kerja (bukan Sabtu/Minggu atau
 *    hari tanpa shift);
 * 4. update status + approver + waktu + catatan.
 *
 * `saldo_cuti` sengaja tidak diubah: konsumsi diturunkan dari baris presensi
 * + saldo (docs/database-design.md aturan #4).
 */
class ApprovePengajuanAction
{
    public function __construct(
        private readonly DaftarHariKerjaAction $daftarHariKerja,
    ) {}

    /**
     * @return array{pengajuan: Pengajuan, jumlahPresensi: int}
     */
    public function __invoke(User $admin, Pengajuan $pengajuan, ?string $catatan = null): array
    {
        Gate::forUser($admin)->authorize('decide', $pengajuan);

        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));

        try {
            return DB::transaction(function () use ($admin, $pengajuan, $catatan, $sekarang): array {
                $terkunci = Pengajuan::query()
                    ->whereKey($pengajuan->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($terkunci->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'pengajuan' => 'Pengajuan ini sudah diputuskan sebelumnya.',
                    ]);
                }

                $mulai = CarbonImmutable::parse((string) $terkunci->tanggal_mulai)->startOfDay();
                $selesai = CarbonImmutable::parse((string) $terkunci->tanggal_berakhir)->startOfDay();

                if (TukinPeriode::adaTerkunciDalamRentang($mulai, $selesai)) {
                    throw ValidationException::withMessages([
                        'pengajuan' => 'Tidak bisa disetujui: periode TUKIN pada rentang ini sudah dikunci.',
                    ]);
                }

                $presensiBentrok = Presensi::query()
                    ->where('id_pegawai', $terkunci->id_pegawai)
                    ->whereBetween('tgl_presensi', [$mulai->toDateString(), $selesai->toDateString()])
                    ->orderBy('tgl_presensi')
                    ->first(['tgl_presensi', 'status']);

                if ($presensiBentrok !== null) {
                    $tanggal = CarbonImmutable::parse((string) $presensiBentrok->tgl_presensi)->format('d/m/Y');
                    $jenis = $presensiBentrok->status === 'hadir'
                        ? 'presensi hadir'
                        : "presensi {$presensiBentrok->status}";

                    throw ValidationException::withMessages([
                        'pengajuan' => "Tidak bisa disetujui: sudah ada {$jenis} pada {$tanggal}. "
                            .'Tolak pengajuan ini atau perbaiki data presensi tersebut.',
                    ]);
                }

                $pengajuanBentrok = Pengajuan::query()
                    ->where('id_pegawai', $terkunci->id_pegawai)
                    ->where('id', '!=', $terkunci->id)
                    ->where('status', 'approved')
                    ->where('tanggal_mulai', '<=', $selesai->toDateString())
                    ->where('tanggal_berakhir', '>=', $mulai->toDateString())
                    ->orderBy('tanggal_mulai')
                    ->first(['tanggal_mulai', 'tanggal_berakhir']);

                if ($pengajuanBentrok !== null) {
                    $dari = CarbonImmutable::parse((string) $pengajuanBentrok->tanggal_mulai)->format('d/m/Y');
                    $sampai = CarbonImmutable::parse((string) $pengajuanBentrok->tanggal_berakhir)->format('d/m/Y');

                    throw ValidationException::withMessages([
                        'pengajuan' => "Tidak bisa disetujui: rentang bertabrakan dengan pengajuan approved {$dari} – {$sampai}.",
                    ]);
                }

                $terkunci->loadMissing('pegawai');

                $dlSetengah = $terkunci->jenis === 'dl'
                    && $terkunci->mode_dl === Pengajuan::MODE_DL_SETENGAH;

                // DL setengah: pegawai absen sendiri sekali; jangan buat baris otomatis.
                $hariKerja = $dlSetengah
                    ? []
                    : ($this->daftarHariKerja)($terkunci->pegawai, $mulai, $selesai);

                $catatan = $catatan === null ? null : trim($catatan);

                $terkunci->update([
                    'status' => 'approved',
                    'disetujui_oleh' => $admin->id,
                    'disetujui_pada' => $sekarang->toDateTimeString(),
                    'catatan' => $catatan === '' ? null : $catatan,
                ]);

                foreach ($hariKerja as $hari) {
                    Presensi::query()->create([
                        'id_pegawai' => $terkunci->id_pegawai,
                        'id_jam_kerja' => $hari['id_jam_kerja'],
                        'id_pengajuan' => $terkunci->id,
                        'tgl_presensi' => $hari['tanggal'],
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'foto_masuk' => null,
                        'foto_keluar' => null,
                        'koordinat_masuk' => null,
                        'koordinat_keluar' => null,
                        'status' => $terkunci->jenis,
                    ]);
                }

                return [
                    'pengajuan' => $terkunci->refresh(),
                    'jumlahPresensi' => count($hariKerja),
                ];
            });
        } catch (UniqueConstraintViolationException $error) {
            // Race: presensi baru masuk setelah pengecekan. Transaksi sudah rollback.
            throw ValidationException::withMessages([
                'pengajuan' => 'Persetujuan gagal karena ada presensi baru di rentang tanggal tersebut. Coba lagi.',
            ]);
        }
    }
}
