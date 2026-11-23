<?php

/**
 * Rasio kontras WCAG 2.1 antara dua warna heksadesimal.
 */
function kontrasWarna(string $hexA, string $hexB): float
{
    $luminansi = static function (string $warna): float {
        $warna = ltrim($warna, '#');
        $kanal = [];

        for ($i = 0; $i < 3; $i++) {
            $nilai = hexdec(substr($warna, $i * 2, 2)) / 255;
            $kanal[] = $nilai <= 0.03928 ? $nilai / 12.92 : (($nilai + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $kanal[0] + 0.7152 * $kanal[1] + 0.0722 * $kanal[2];
    };

    $a = $luminansi($hexA);
    $b = $luminansi($hexB);
    $terang = max($a, $b);
    $gelap = min($a, $b);

    return ($terang + 0.05) / ($gelap + 0.05);
}

/**
 * Baca semua token warna brand/accent/info dari design token.
 *
 * @return array<string, string> kunci "brand-500" => "#00b9ad"
 */
function tokenWarna(): array
{
    $css = file_get_contents(resource_path('css/app.css'));

    preg_match_all('/--color-(brand|accent|info)-(\d{2,3}):\s*(#[0-9a-f]{6})/i', $css, $cocok, PREG_SET_ORDER);

    $token = [];

    foreach ($cocok as $baris) {
        $token[$baris[1].'-'.$baris[2]] = strtolower($baris[3]);
    }

    return $token;
}

test('defines eleven steps for every kemenkes palette', function () {
    $token = tokenWarna();

    expect($token)->toHaveCount(33);

    foreach (['brand', 'accent', 'info'] as $palet) {
        $langkah = array_filter(array_keys($token), fn (string $kunci) => str_starts_with($kunci, $palet.'-'));

        expect($langkah)->toHaveCount(11);
    }
});

test('keeps the exact kemenkes logo colour at step 500', function () {
    $token = tokenWarna();

    expect($token['brand-500'])->toBe('#00b9ad')
        ->and($token['accent-500'])->toBe('#cddc29')
        ->and($token['info-500'])->toBe('#60c0d0');
});

test('makes step 600 safe for ui elements on white', function () {
    foreach (tokenWarna() as $kunci => $warna) {
        $langkah = (int) explode('-', $kunci)[1];

        if ($langkah !== 600) {
            continue;
        }

        // WCAG 1.4.11 — elemen non-teks wajib 3:1.
        expect(kontrasWarna($warna, '#ffffff'))->toBeGreaterThanOrEqual(3.0);
    }
});

test('makes step 700 and darker readable as text on white', function () {
    foreach (tokenWarna() as $kunci => $warna) {
        $langkah = (int) explode('-', $kunci)[1];

        if ($langkah < 700) {
            continue;
        }

        // WCAG 1.4.3 — teks normal wajib 4.5:1.
        expect(kontrasWarna($warna, '#ffffff'))->toBeGreaterThanOrEqual(4.5);
    }
});

test('keeps step 500 and lighter out of text usage', function () {
    foreach (tokenWarna() as $kunci => $warna) {
        $langkah = (int) explode('-', $kunci)[1];

        if ($langkah > 500) {
            continue;
        }

        expect(kontrasWarna($warna, '#ffffff'))->toBeLessThan(3.0);
    }
});

test('keeps dark mode out of every blade view', function () {
    // Keputusan QA 23 Nov 2026: dark mode tidak didukung sama sekali.
    $melanggar = [];
    $semua = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($semua as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        if (str_contains(file_get_contents($file->getPathname()), 'dark:')) {
            $melanggar[] = str_replace(resource_path('views').'/', '', $file->getPathname());
        }
    }

    expect($melanggar)->toBeEmpty();
});
