<?php

namespace App\Actions\Pengaturan;

use App\Models\Pengaturan;

/**
 * Ambil titik kantor dan radius absen yang berlaku.
 *
 * Nilai dari tabel `pengaturan` menang atas config/.env supaya admin dapat
 * mengubahnya dari halaman Konfigurasi. Bila admin belum pernah menyimpan,
 * fallback ke config menjaga perilaku instalasi lama.
 */
class AmbilLokasiKantorAction
{
    /**
     * @return array{latitude: string, longitude: string, radius: int}
     */
    public function __invoke(): array
    {
        return [
            'latitude' => (string) Pengaturan::nilai(
                'lokasi_latitude',
                (string) config('presensi.latitude'),
            ),
            'longitude' => (string) Pengaturan::nilai(
                'lokasi_longitude',
                (string) config('presensi.longitude'),
            ),
            'radius' => (int) Pengaturan::nilai(
                'lokasi_radius',
                (string) config('presensi.radius'),
            ),
        ];
    }
}
