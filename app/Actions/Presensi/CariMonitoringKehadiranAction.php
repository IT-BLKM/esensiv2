<?php

namespace App\Actions\Presensi;

use App\Models\Presensi;
use Illuminate\Support\Carbon;

/**
 * Data monitoring kehadiran satu tanggal.
 *
 * Query memakai `where('tgl_presensi', ...)` supaya memanfaatkan index
 * `tgl_presensi`; filter departemen memakai relasi Eloquent (`whereHas`),
 * tanpa `whereRaw` atau konkatenasi SQL.
 */
class CariMonitoringKehadiranAction
{
    public function __construct(
        private readonly HitungMenitTerlambatAction $hitungMenitTerlambat,
    ) {}

    /**
     * @return array{
     *     baris: list<array{
     *         id: int,
     *         nama: string,
     *         nip: string,
     *         departemen: string|null,
     *         shift: string|null,
     *         status: string,
     *         statusLabel: string,
     *         jamMasuk: string|null,
     *         jamKeluar: string|null,
     *         menitTerlambat: int,
     *         koordinatMasuk: array{latitude: float, longitude: float, teks: string}|null,
     *         koordinatKeluar: array{latitude: float, longitude: float, teks: string}|null
     *     }>,
     *     titik: list<array{
     *         id: int,
     *         jenis: string,
     *         nama: string,
     *         nip: string,
     *         status: string,
     *         statusLabel: string,
     *         jam: string,
     *         latitude: float,
     *         longitude: float
     *     }>,
     *     ringkasan: array{total: int, hadir: int, cuti: int, sakit: int, dl: int}
     * }
     */
    public function __invoke(string $tanggal, ?int $idDept = null, ?string $status = null): array
    {
        $presensi = Presensi::query()
            ->with([
                'pegawai:id,nip,nama,id_dept',
                'pegawai.departemen:id,kode,nama',
                'jamKerja:id,kode,nama,jam_masuk,jam_pulang',
                'pengajuan',
            ])
            ->where('tgl_presensi', $tanggal)
            ->when(
                $idDept !== null,
                fn ($query) => $query->whereHas(
                    'pegawai',
                    fn ($query) => $query->where('id_dept', $idDept),
                ),
            )
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderBy('jam_masuk')
            ->orderBy('id')
            ->get();

        $baris = [];
        $titik = [];
        $ringkasan = ['total' => 0, 'hadir' => 0, 'cuti' => 0, 'sakit' => 0, 'dl' => 0];

        foreach ($presensi as $item) {
            $koordinatMasuk = $this->koordinat($item->koordinat_masuk);
            $koordinatKeluar = $this->koordinat($item->koordinat_keluar);
            $statusLabel = Presensi::LABEL_STATUS[$item->status] ?? ucfirst($item->status);
            $jamMasuk = $this->formatJam($item->jam_masuk);
            $jamKeluar = $this->formatJam($item->jam_keluar);
            $nama = $item->pegawai?->nama ?? '—';
            $nip = $item->pegawai?->nip ?? '—';

            $ringkasan['total']++;
            $ringkasan[$item->status] = ($ringkasan[$item->status] ?? 0) + 1;

            $baris[] = [
                'id' => $item->id,
                'nama' => $nama,
                'nip' => $nip,
                'departemen' => $item->pegawai?->departemen?->nama,
                'shift' => $item->jamKerja?->nama,
                'status' => $item->status,
                'statusLabel' => $statusLabel,
                'jamMasuk' => $jamMasuk,
                'jamKeluar' => $jamKeluar,
                'menitTerlambat' => $this->menitTerlambat($item),
                'koordinatMasuk' => $koordinatMasuk,
                'koordinatKeluar' => $koordinatKeluar,
            ];

            if ($koordinatMasuk !== null && $jamMasuk !== null) {
                $titik[] = [
                    'id' => $item->id,
                    'jenis' => 'masuk',
                    'nama' => $nama,
                    'nip' => $nip,
                    'status' => $item->status,
                    'statusLabel' => $statusLabel,
                    'jam' => $jamMasuk,
                    'latitude' => $koordinatMasuk['latitude'],
                    'longitude' => $koordinatMasuk['longitude'],
                ];
            }

            if ($koordinatKeluar !== null && $jamKeluar !== null) {
                $titik[] = [
                    'id' => $item->id,
                    'jenis' => 'keluar',
                    'nama' => $nama,
                    'nip' => $nip,
                    'status' => $item->status,
                    'statusLabel' => $statusLabel,
                    'jam' => $jamKeluar,
                    'latitude' => $koordinatKeluar['latitude'],
                    'longitude' => $koordinatKeluar['longitude'],
                ];
            }
        }

        return [
            'baris' => $baris,
            'titik' => $titik,
            'ringkasan' => $ringkasan,
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, teks: string}|null
     */
    private function koordinat(?string $teks): ?array
    {
        if ($teks === null || $teks === '') {
            return null;
        }

        $bagian = array_map('trim', explode(',', $teks, 2));

        if (count($bagian) !== 2 || ! is_numeric($bagian[0]) || ! is_numeric($bagian[1])) {
            return null;
        }

        return [
            'latitude' => (float) $bagian[0],
            'longitude' => (float) $bagian[1],
            'teks' => $teks,
        ];
    }

    private function formatJam(?string $jam): ?string
    {
        return $jam === null || $jam === '' ? null : Carbon::parse($jam)->format('H:i');
    }

    /**
     * Keterlambatan memakai sumber yang sama dengan dashboard/histori.
     */
    private function menitTerlambat(Presensi $presensi): int
    {
        return ($this->hitungMenitTerlambat)($presensi);
    }
}
