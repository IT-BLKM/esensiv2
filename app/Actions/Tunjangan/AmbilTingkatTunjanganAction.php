<?php

namespace App\Actions\Tunjangan;

use App\Models\TunjanganTingkat;
use Illuminate\Support\Collection;

/**
 * Ambil aturan tingkat potongan dari database, fallback ke config.
 *
 * Cache hanya berlaku selama satu instance/request agar perubahan admin
 * langsung terlihat pada request berikutnya.
 */
class AmbilTingkatTunjanganAction
{
    /**
     * @var array<string, Collection<int, array{level: string, min: int, max: int|null, persen: float}>>
     */
    private array $cache = [];

    /**
     * @return Collection<int, array{level: string, min: int, max: int|null, persen: float}>
     */
    public function __invoke(string $kategori): Collection
    {
        if (isset($this->cache[$kategori])) {
            return $this->cache[$kategori];
        }

        $dariDatabase = TunjanganTingkat::query()
            ->where('kategori', $kategori)
            ->orderBy('min')
            ->get()
            ->map(fn (TunjanganTingkat $tingkat): array => [
                'level' => $tingkat->level,
                'min' => (int) $tingkat->min,
                'max' => $tingkat->max === null ? null : (int) $tingkat->max,
                'persen' => (float) $tingkat->persen,
            ]);

        if ($dariDatabase->isNotEmpty()) {
            return $this->cache[$kategori] = $dariDatabase->values();
        }

        $dariConfig = collect(config("tunjangan.{$kategori}", []))
            ->map(fn (array $tingkat): array => [
                'level' => (string) $tingkat['level'],
                'min' => (int) $tingkat['min'],
                'max' => $tingkat['max'] === null ? null : (int) $tingkat['max'],
                'persen' => (float) $tingkat['persen'],
            ])
            ->values();

        return $this->cache[$kategori] = $dariConfig;
    }
}
