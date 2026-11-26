<?php

namespace App\Actions\Tukin;

use App\Models\TukinKoreksi;
use Illuminate\Support\Collection;

/**
 * Tambahkan koreksi manual dan nominal rupiah ke hasil hitung TUKIN.
 *
 * Persentase hasil hitung tetap disimpan sebagai `totalPersen`; angka setelah
 * koreksi ada di `totalPersenAkhir`. Bila nominal jabatan tidak diatur,
 * `nominal` dan `potonganNominal` bernilai null.
 */
class LengkapiTukinAction
{
    /**
     * @param  array<string, mixed>  $hasil
     * @param  Collection<int, TukinKoreksi>  $koreksi
     * @return array<string, mixed>
     */
    public function __invoke(array $hasil, ?int $nominal, Collection $koreksi): array
    {
        $koreksiPersen = round((float) $koreksi->sum('persen'), 2);
        $totalPersenAwal = (float) ($hasil['ringkasan']['totalPersen'] ?? 0);
        $totalPersenAkhir = round($totalPersenAwal + $koreksiPersen, 2);
        $potonganNominal = $nominal === null
            ? null
            : (int) round($nominal * $totalPersenAkhir / 100);

        $hasil['ringkasan']['koreksiPersen'] = $koreksiPersen;
        $hasil['ringkasan']['totalPersenAwal'] = $totalPersenAwal;
        $hasil['ringkasan']['totalPersenAkhir'] = $totalPersenAkhir;
        $hasil['ringkasan']['nominal'] = $nominal;
        $hasil['ringkasan']['potonganNominal'] = $potonganNominal;
        $hasil['koreksi'] = $koreksi
            ->map(fn (TukinKoreksi $item): array => [
                'id' => $item->id,
                'persen' => $item->persen,
                'alasan' => $item->alasan,
                'pembuat' => $item->pembuat?->getAttribute('nama'),
                'dibuat_pada' => $item->created_at?->format('d-m-Y H:i'),
            ])
            ->values()
            ->all();

        if (isset($hasil['baris']) && is_array($hasil['baris'])) {
            foreach ($hasil['baris'] as &$baris) {
                $baris['potonganNominal'] = $nominal === null
                    ? null
                    : (int) round($nominal * (float) $baris['persenTotal'] / 100);
            }
            unset($baris);
        }

        return $hasil;
    }
}
