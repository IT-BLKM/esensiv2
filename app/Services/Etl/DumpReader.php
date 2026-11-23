<?php

namespace App\Services\Etl;

use RuntimeException;

/**
 * Pembaca dump SQL hasil mysqldump dari sistem lama (presensigps).
 *
 * Membaca semua statement `INSERT INTO `tabel` (kolom) VALUES ...`
 * dan mengembalikan barisnya per tabel dalam bentuk array asosiatif.
 * Lihat docs/etl-runbook.md bagian 2 (pemetaan tabel).
 */
class DumpReader
{
    /**
     * Baca file dump dan kelompokkan barisnya per nama tabel.
     *
     * @param  string  $path  lokasi file dump (wajib di luar repo)
     * @return array<string, list<array<string, string|int|null>>>
     *
     * @throws RuntimeException bila file tidak bisa dibaca
     */
    public function read(string $path): array
    {
        $sql = @file_get_contents($path);

        if ($sql === false) {
            throw new RuntimeException("File dump tidak bisa dibaca: {$path}");
        }

        $tables = [];

        $ditemukan = preg_match_all(
            '/INSERT INTO `([^`]+)`\s*\(([^)]*)\)\s*VALUES\s*/i',
            $sql,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if ($ditemukan === false || $ditemukan === 0) {
            return $tables;
        }

        foreach ($matches[0] as $index => $keseluruhan) {
            $tabel = $matches[1][$index][0];
            $kolom = array_map(
                static fn (string $nama): string => trim($nama, " `\t\n\r"),
                explode(',', $matches[2][$index][0])
            );
            $mulai = $keseluruhan[1] + strlen($keseluruhan[0]);
            $baris = $this->parseTuple($sql, $mulai, $kolom);
            $tables[$tabel] = array_merge($tables[$tabel] ?? [], $baris);
        }

        return $tables;
    }

    /**
     * Parse daftar tuple `(v1, v2, ...), (...)` mulai dari $mulai sampai akhir statement.
     *
     * @param  list<string>  $kolom
     * @return list<array<string, string|int|null>>
     */
    private function parseTuple(string $sql, int $mulai, array $kolom): array
    {
        $hasil = [];
        $panjang = strlen($sql);
        $i = $mulai;

        while ($i < $panjang) {
            $karakter = $sql[$i];

            if (ctype_space($karakter) || $karakter === ',') {
                $i++;

                continue;
            }

            if ($karakter !== '(') {
                break; // akhir statement (';') atau konten tak terduga
            }

            $i++; // lewati '('
            $nilai = [];
            $mentah = '';
            $diDalamString = false;
            $escaped = false;
            $tertutup = false;

            while ($i < $panjang) {
                $karakter = $sql[$i];

                if ($diDalamString) {
                    $mentah .= $karakter;
                    if ($escaped) {
                        $escaped = false;
                    } elseif ($karakter === '\\') {
                        $escaped = true;
                    } elseif ($karakter === "'") {
                        $diDalamString = false;
                    }
                    $i++;

                    continue;
                }

                if ($karakter === "'") {
                    $diDalamString = true;
                    $mentah .= $karakter;
                    $i++;

                    continue;
                }

                if ($karakter === ',') {
                    $nilai[] = $mentah;
                    $mentah = '';
                    $i++;

                    continue;
                }

                if ($karakter === ')') {
                    $nilai[] = $mentah;
                    $mentah = '';
                    $i++;
                    $tertutup = true;
                    break;
                }

                $mentah .= $karakter;
                $i++;
            }

            if (! $tertutup || count($nilai) !== count($kolom)) {
                break; // tuple rusak atau jumlah kolom tidak cocok — hentikan pembacaan statement ini
            }

            $baris = [];
            foreach ($kolom as $indeks => $nama) {
                $baris[$nama] = $this->castNilai($nilai[$indeks]);
            }
            $hasil[] = $baris;
        }

        return $hasil;
    }

    /**
     * Normalisasi satu nilai mentah: NULL → null, string berkuot → string, angka → int.
     */
    private function castNilai(string $mentah): string|int|null
    {
        $mentah = trim($mentah);

        if ($mentah === '' || strcasecmp($mentah, 'NULL') === 0) {
            return null;
        }

        if (strlen($mentah) >= 2 && $mentah[0] === "'" && str_ends_with($mentah, "'")) {
            return str_replace(
                ['\\\\', "\\'", '\\n', '\\r', '\\0', '\\Z', '\\"'],
                ['\\', "'", "\n", "\r", "\0", chr(26), '"'],
                substr($mentah, 1, -1)
            );
        }

        if (preg_match('/^-?\d+$/', $mentah) === 1) {
            return (int) $mentah;
        }

        return $mentah;
    }
}
