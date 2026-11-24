<?php

namespace App\Actions\KonfigurasiJamKerja;

use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;

class UpdateKonfigurasiJamKerjaAction
{
    /**
     * Simpan jadwal tujuh hari dengan updateOrCreate, bukan insert dobel.
     * Hari tanpa shift dihapus supaya tidak ada baris "kosong" di database.
     *
     * @param  array<int, array{hari: string, id_jam_kerja?: int|null}>  $jadwal
     */
    public function __invoke(Pegawai $pegawai, array $jadwal): int
    {
        $shiftPerHari = collect($jadwal)->mapWithKeys(function (array $item): array {
            return [$item['hari'] => $item['id_jam_kerja'] ?? null];
        });

        DB::transaction(function () use ($pegawai, $shiftPerHari): void {
            foreach (KonfigurasiJamKerja::HARI as $hari) {
                $idJamKerja = $shiftPerHari->get($hari);

                if ($idJamKerja === null) {
                    KonfigurasiJamKerja::query()
                        ->where('id_pegawai', $pegawai->id)
                        ->where('hari', $hari)
                        ->delete();

                    continue;
                }

                KonfigurasiJamKerja::query()->updateOrCreate(
                    ['id_pegawai' => $pegawai->id, 'hari' => $hari],
                    ['id_jam_kerja' => $idJamKerja],
                );
            }
        });

        return KonfigurasiJamKerja::query()
            ->where('id_pegawai', $pegawai->id)
            ->count();
    }
}
