<?php

namespace App\Actions\Laporan;

use App\Actions\Presensi\HitungMenitPulangCepatAction;
use App\Actions\Presensi\HitungMenitTerlambatAction;
use App\Actions\Presensi\HitungPenggantianKeterlambatanAction;
use App\Models\Presensi;
use Illuminate\Support\Collection;

/**
 * Terjemahkan satu baris presensi menjadi kode laporan harian.
 *
 * Kode yang dihasilkan:
 * - H, DL, C, S untuk status dasar.
 * - H! untuk hadir terlambat yang sudah diganti (tidak memotong tukin).
 * - TL1/TL2/TL3 untuk terlambat yang masih memotong tukin.
 * - PSW1–PSW4 untuk pulang sebelum waktu.
 *
 * Satu hari bisa menghasilkan dua baris kode (mis. TL1 + PSW2). View/Excel
 * menampilkannya bertingkat agar kolom tetap sempit.
 */
class KlasifikasiHariPresensiAction
{
    public function __construct(
        private readonly HitungMenitTerlambatAction $hitungMenitTerlambat,
        private readonly HitungMenitPulangCepatAction $hitungMenitPulangCepat,
        private readonly HitungPenggantianKeterlambatanAction $hitungPenggantian,
    ) {}

    /**
     * @return array{
     *     kodeStatus: string,
     *     kodeTl: string|null,
     *     levelTl: string|null,
     *     kodePsw: string|null,
     *     levelPsw: string|null,
     *     baris: list<string>,
     *     terlambat: bool,
     *     pulangCepat: bool,
     *     terlambatDiganti: bool
     * }
     */
    public function __invoke(Presensi $presensi): array
    {
        $kodeStatus = $this->kodeStatus($presensi->status);

        if ($presensi->status !== 'hadir') {
            return [
                'kodeStatus' => $kodeStatus,
                'kodeTl' => null,
                'levelTl' => null,
                'kodePsw' => null,
                'levelPsw' => null,
                'baris' => [$kodeStatus],
                'terlambat' => false,
                'pulangCepat' => false,
                'terlambatDiganti' => false,
            ];
        }

        $menitTerlambat = ($this->hitungMenitTerlambat)($presensi);
        $menitPulangCepat = ($this->hitungMenitPulangCepat)($presensi);
        $penggantian = $menitTerlambat > 0 ? ($this->hitungPenggantian)($presensi) : null;

        $kodeTl = null;
        $levelTl = null;
        $terlambatDiganti = false;
        $baris = [];

        if ($menitTerlambat > 0) {
            if (($penggantian['terpenuhi'] ?? false) === true) {
                $kodeTl = 'H!';
                $terlambatDiganti = true;
            } else {
                $levelTl = $this->tingkat('keterlambatan', $menitTerlambat);
                $kodeTl = $levelTl ?? 'H!';
            }

            $baris[] = $kodeTl;
        }

        $kodePsw = null;
        $levelPsw = null;

        if ($menitPulangCepat > 0) {
            $levelPsw = $this->tingkat('pulang_cepat', $menitPulangCepat);
            $kodePsw = $levelPsw ?? 'PSW';
            $baris[] = $kodePsw;
        }

        if ($baris === []) {
            $baris[] = 'H';
        }

        return [
            'kodeStatus' => $kodeStatus,
            'kodeTl' => $kodeTl,
            'levelTl' => $levelTl,
            'kodePsw' => $kodePsw,
            'levelPsw' => $levelPsw,
            'baris' => $baris,
            'terlambat' => $menitTerlambat > 0,
            'pulangCepat' => $menitPulangCepat > 0,
            'terlambatDiganti' => $terlambatDiganti,
        ];
    }

    private function kodeStatus(string $status): string
    {
        return match ($status) {
            'hadir' => 'H',
            'dl' => 'DL',
            'cuti' => 'C',
            'sakit' => 'S',
            default => 'A',
        };
    }

    /**
     * @return Collection<int, array{level: string, min: int, max: int|null, persen: float}>
     */
    private function daftarTingkat(string $kunci): Collection
    {
        return collect(config("tunjangan.{$kunci}", []));
    }

    private function tingkat(string $kunci, int $menit): ?string
    {
        $tingkat = $this->daftarTingkat($kunci)->first(function (array $item) use ($menit): bool {
            return $menit >= $item['min']
                && ($item['max'] === null || $menit <= $item['max']);
        });

        return $tingkat['level'] ?? null;
    }
}
