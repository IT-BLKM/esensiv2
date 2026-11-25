<?php

namespace App\Actions\Presensi;

use App\Models\JamKerja;
use App\Models\Pengajuan;
use App\Models\PenggantianKeterlambatan;
use App\Models\Presensi;
use Carbon\CarbonImmutable;

/**
 * Hitung penggantian waktu keterlambatan pada hari yang sama.
 *
 * Aturan (keputusan QA, issue #109):
 * - Hanya keterlambatan 1–30 menit yang bisa diganti.
 * - Target pulang = jam pulang shift + menit terlambat.
 * - Jendela absen pulang = target s.d. target + 5 menit.
 * - Telat >30 menit tidak bisa diganti dan langsung mengikuti TL2/TL3.
 * - Penggantian hanya menghapus TL; PSW tetap dihitung terpisah.
 *
 * Snapshot audit disimpan di `penggantian_keterlambatan` saat absen pulang.
 * Baris lama tanpa snapshot tetap bisa dihitung dari jam masuk/keluar.
 */
class HitungPenggantianKeterlambatanAction
{
    private const MAKS_MENIT_DIGANTI = 30;

    private const TOLERANSI_MENIT = 5;

    public function __construct(
        private readonly HitungMenitTerlambatAction $hitungMenitTerlambat,
    ) {}

    /**
     * @return array{
     *     tanggal: string,
     *     bisaDiganti: bool,
     *     menitTerlambat: int,
     *     jamMulai: string,
     *     jamSelesai: string|null,
     *     targetPulang: string|null,
     *     batasPulang: string|null,
     *     menitDiganti: int,
     *     terpenuhi: bool,
     *     catatan: string
     * }|null
     */
    public function __invoke(Presensi $presensi, ?JamKerja $shift = null): ?array
    {
        $snapshot = $presensi->relationLoaded('penggantianKeterlambatan')
            ? $presensi->penggantianKeterlambatan
            : $presensi->penggantianKeterlambatan()->first();

        if ($snapshot instanceof PenggantianKeterlambatan) {
            return $this->dariSnapshot($snapshot);
        }

        if ($presensi->status !== 'hadir' || $presensi->jam_masuk === null) {
            return null;
        }

        $shift ??= $presensi->jamKerja;

        if ($shift === null || $shift->jam_pulang === null || $shift->jam_pulang === '') {
            return null;
        }

        if (in_array($shift->kode, $this->kodeJamKerjaDiabaikan(), true)) {
            return null;
        }

        if ($this->dinasLuarSetengahHari($presensi)) {
            return null;
        }

        $menitTerlambat = ($this->hitungMenitTerlambat)($presensi);

        if ($menitTerlambat <= 0) {
            return null;
        }

        $tanggal = (string) $presensi->tgl_presensi;
        $jamMulai = (string) $shift->jam_pulang;
        $pulangShift = $this->waktu($tanggal, $jamMulai);
        $bisaDiganti = $menitTerlambat <= self::MAKS_MENIT_DIGANTI;

        if (! $bisaDiganti) {
            return [
                'tanggal' => $tanggal,
                'bisaDiganti' => false,
                'menitTerlambat' => $menitTerlambat,
                'jamMulai' => $jamMulai,
                'jamSelesai' => $presensi->jam_keluar,
                'targetPulang' => null,
                'batasPulang' => null,
                'menitDiganti' => 0,
                'terpenuhi' => false,
                'catatan' => "Terlambat {$menitTerlambat} menit; lebih dari 30 menit sehingga tidak dapat diganti.",
            ];
        }

        $target = $pulangShift->addMinutes($menitTerlambat);
        $batas = $target->addMinutes(self::TOLERANSI_MENIT);
        $targetTeks = $target->format('H:i');
        $batasTeks = $batas->format('H:i');

        if ($presensi->jam_keluar === null) {
            return [
                'tanggal' => $tanggal,
                'bisaDiganti' => true,
                'menitTerlambat' => $menitTerlambat,
                'jamMulai' => $jamMulai,
                'jamSelesai' => null,
                'targetPulang' => $target->format('H:i:s'),
                'batasPulang' => $batas->format('H:i:s'),
                'menitDiganti' => 0,
                'terpenuhi' => false,
                'catatan' => "Terlambat {$menitTerlambat} menit; belum absen pulang (target {$targetTeks}–{$batasTeks}).",
            ];
        }

        $keluar = $this->waktu($tanggal, (string) $presensi->jam_keluar);
        $selisihDetik = $keluar->getTimestamp() - $pulangShift->getTimestamp();
        $menitDiganti = $selisihDetik > 0 ? (int) floor($selisihDetik / 60) : 0;
        $terpenuhi = $keluar->gte($target) && $keluar->lte($batas);
        $keluarTeks = $keluar->format('H:i');

        $catatan = match (true) {
            $terpenuhi => "Terlambat {$menitTerlambat} menit; pulang {$keluarTeks} mengganti dalam jendela {$targetTeks}–{$batasTeks}.",
            $keluar->lt($target) => "Terlambat {$menitTerlambat} menit; pulang {$keluarTeks} belum mencapai target {$targetTeks}.",
            default => "Terlambat {$menitTerlambat} menit; pulang {$keluarTeks} melewati batas {$batasTeks}.",
        };

        return [
            'tanggal' => $tanggal,
            'bisaDiganti' => true,
            'menitTerlambat' => $menitTerlambat,
            'jamMulai' => $jamMulai,
            'jamSelesai' => (string) $presensi->jam_keluar,
            'targetPulang' => $target->format('H:i:s'),
            'batasPulang' => $batas->format('H:i:s'),
            'menitDiganti' => $menitDiganti,
            'terpenuhi' => $terpenuhi,
            'catatan' => $catatan,
        ];
    }

    /**
     * @return array{
     *     tanggal: string,
     *     bisaDiganti: bool,
     *     menitTerlambat: int,
     *     jamMulai: string,
     *     jamSelesai: string|null,
     *     targetPulang: string|null,
     *     batasPulang: string|null,
     *     menitDiganti: int,
     *     terpenuhi: bool,
     *     catatan: string
     * }
     */
    private function dariSnapshot(PenggantianKeterlambatan $snapshot): array
    {
        return [
            'tanggal' => $snapshot->tanggal->toDateString(),
            'bisaDiganti' => $snapshot->bisa_diganti,
            'menitTerlambat' => $snapshot->menit_terlambat,
            'jamMulai' => (string) $snapshot->jam_mulai,
            'jamSelesai' => $snapshot->jam_selesai === null ? null : (string) $snapshot->jam_selesai,
            'targetPulang' => $snapshot->target_pulang === null ? null : (string) $snapshot->target_pulang,
            'batasPulang' => $snapshot->batas_pulang === null ? null : (string) $snapshot->batas_pulang,
            'menitDiganti' => $snapshot->menit_diganti,
            'terpenuhi' => $snapshot->terpenuhi,
            'catatan' => (string) $snapshot->catatan,
        ];
    }

    private function dinasLuarSetengahHari(Presensi $presensi): bool
    {
        $pengajuan = $presensi->pengajuan;

        return $pengajuan !== null
            && $pengajuan->jenis === 'dl'
            && $pengajuan->mode_dl === Pengajuan::MODE_DL_SETENGAH;
    }

    private function waktu(string $tanggal, string $jam): CarbonImmutable
    {
        return CarbonImmutable::parse("{$tanggal} {$jam}", (string) config('presensi.zona_waktu', 'Asia/Jakarta'));
    }

    /**
     * @return list<string>
     */
    private function kodeJamKerjaDiabaikan(): array
    {
        $kode = config('presensi.kode_jam_kerja_diabaikan', []);

        return is_array($kode) ? array_values($kode) : [];
    }
}
