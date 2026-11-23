<?php

use App\Services\Etl\DumpReader;

test('membaca baris dengan NULL, angka, dan string berspasi/koma', function () {
    $sql = <<<'SQL'
INSERT INTO `pegawai` (`nip`, `nama_lengkap`, `jabatan`, `foto`, `no_hp`) VALUES
('199302032022032001', 'Budi, S.Kom', 'Staf  (Umum)', NULL, '0812'),
('198009082003122003', 'Ani', NULL, 'null', '0813');
SQL;
    $path = tulisDumpLama($sql);

    try {
        $hasil = (new DumpReader)->read($path);
    } finally {
        unlink($path);
    }

    expect($hasil)->toHaveKey('pegawai')
        ->and($hasil['pegawai'])->toHaveCount(2)
        ->and($hasil['pegawai'][0]['nip'])->toBe('199302032022032001')
        ->and($hasil['pegawai'][0]['jabatan'])->toBe('Staf  (Umum)')
        ->and($hasil['pegawai'][0]['foto'])->toBeNull()
        ->and($hasil['pegawai'][1]['jabatan'])->toBeNull()
        ->and($hasil['pegawai'][1]['foto'])->toBe('null');
});

test('menggabungkan beberapa statement INSERT untuk tabel yang sama', function () {
    $sql = <<<'SQL'
INSERT INTO `presensi` (`id`, `tgl_presensi`, `status`) VALUES (1, '2026-01-05', 'h');
INSERT INTO `presensi` (`id`, `tgl_presensi`, `status`) VALUES (2, '2026-01-06', 'c'), (3, '2026-01-07', 's');
SQL;
    $path = tulisDumpLama($sql);

    try {
        $hasil = (new DumpReader)->read($path);
    } finally {
        unlink($path);
    }

    expect($hasil['presensi'])->toHaveCount(3)
        ->and($hasil['presensi'][0]['id'])->toBe(1)
        ->and($hasil['presensi'][2]['status'])->toBe('s');
});

test('menangani tanda kutip, kurung, dan titik koma di dalam string', function () {
    $sql = <<<'SQL'
INSERT INTO `pengajuan_cuti` (`kode_cuti`, `keterangan`) VALUES ('CU1', 'Malam; \'Rina\' (25, 26)');
SQL;
    $path = tulisDumpLama($sql);

    try {
        $hasil = (new DumpReader)->read($path);
    } finally {
        unlink($path);
    }

    expect($hasil['pengajuan_cuti'][0]['keterangan'])->toBe("Malam; 'Rina' (25, 26)");
});

test('mengembalikan array kosong untuk file tanpa statement INSERT', function () {
    $path = tulisDumpLama('-- komentar saja');

    try {
        $hasil = (new DumpReader)->read($path);
    } finally {
        unlink($path);
    }

    expect($hasil)->toBe([]);
});

test('melempar RuntimeException untuk file yang tidak terbaca', function () {
    expect(fn () => (new DumpReader)->read('/lokasi/tidak/ada.sql'))
        ->toThrow(RuntimeException::class);
});
