<?php

namespace App\Actions\Pegawai;

use Illuminate\Support\Str;

class BuatPasswordAwalAction
{
    /**
     * Password awal dan password reset: 16 karakter acak yang memakai huruf,
     * angka, dan simbol. Tidak pernah memakai password tetap seperti 123456.
     */
    public function __invoke(): string
    {
        return Str::password(16);
    }
}
