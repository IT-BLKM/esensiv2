<?php

namespace App\Actions\Pengaturan;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\DB;

/**
 * Simpan titik kantor dan radius absen dari form admin.
 */
class SimpanLokasiKantorAction
{
    /**
     * @param  array{latitude: string, longitude: string, radius: int|string}  $data
     */
    public function __invoke(array $data): void
    {
        DB::transaction(function () use ($data): void {
            Pengaturan::simpan('lokasi_latitude', (string) $data['latitude']);
            Pengaturan::simpan('lokasi_longitude', (string) $data['longitude']);
            Pengaturan::simpan('lokasi_radius', (string) $data['radius']);
        });
    }
}
