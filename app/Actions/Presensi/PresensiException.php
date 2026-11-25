<?php

namespace App\Actions\Presensi;

use RuntimeException;

/**
 * Kegagalan bisnis presensi yang harus tampil sebagai JSON terstruktur
 * (`status=error`, `type=...`), bukan error 500.
 */
final class PresensiException extends RuntimeException
{
    public function __construct(
        public readonly string $type,
        string $message,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }

    public static function jadwal(): self
    {
        return new self('jadwal', 'Tidak ada shift terjadwal hari ini, jadi presensi tidak bisa dicatat.');
    }

    public static function radius(float $jarak, int $radius): self
    {
        return new self('radius', sprintf(
            'Lokasi Anda %.0f m dari kantor, melebihi radius %s m. Dekatkan diri ke kantor lalu coba lagi.',
            $jarak,
            number_format($radius, 0, ',', '.'),
        ));
    }

    public static function foto(string $message = 'Foto tidak valid. Ambil ulang foto dari kamera lalu coba lagi.'): self
    {
        return new self('foto', $message);
    }

    public static function fotoGagalDisimpan(): self
    {
        return new self('foto', 'Foto gagal disimpan di server. Coba lagi beberapa saat lagi.', 500);
    }

    public static function sudahMasuk(): self
    {
        return new self('sudah_masuk', 'Absen masuk hari ini sudah tercatat. Gunakan absen pulang.', 409);
    }

    public static function belumMasuk(): self
    {
        return new self('belum_masuk', 'Absen masuk belum tercatat hari ini. Lakukan absen masuk terlebih dahulu.', 409);
    }

    public static function sudahKeluar(): self
    {
        return new self('sudah_keluar', 'Absen pulang hari ini sudah tercatat.', 409);
    }

    public static function statusHariIni(string $status): self
    {
        $label = match ($status) {
            'cuti' => 'cuti',
            'sakit' => 'sakit',
            'dl' => 'dinas luar',
            default => $status,
        };

        return new self('status', "Hari ini tercatat {$label} yang sudah disetujui, jadi presensi tidak bisa ditimpa.", 409);
    }
}
