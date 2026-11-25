<?php

namespace App\Actions\Presensi;

use Illuminate\Support\Carbon;
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

    public static function sebelumAwalMasuk(string $awalMasuk): self
    {
        return new self(
            'waktu',
            'Absen masuk belum dibuka. Jadwal absen masuk mulai pukul '.self::jam($awalMasuk).'.',
        );
    }

    public static function setelahAkhirMasuk(string $akhirMasuk): self
    {
        return new self(
            'waktu',
            'Batas absen masuk sudah lewat pukul '.self::jam($akhirMasuk).'. Hubungi admin bila perlu koreksi.',
        );
    }

    public static function sebelumJamPulang(string $jamPulang): self
    {
        return new self(
            'waktu',
            'Absen pulang belum bisa dilakukan sebelum pukul '.self::jam($jamPulang).'.',
        );
    }

    public static function dlSetengahSatuKali(): self
    {
        return new self(
            'dl_setengah',
            'Dinas Luar setengah hari cukup absen sekali. Absen pulang tidak diperlukan.',
            409,
        );
    }

    public static function dlSetengahLewat(string $jamPulang): self
    {
        return new self(
            'waktu',
            'Absen Dinas Luar setengah hari hanya sampai pukul '.self::jam($jamPulang).'.',
        );
    }

    private static function jam(string $jam): string
    {
        return Carbon::parse($jam)->format('H:i');
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
