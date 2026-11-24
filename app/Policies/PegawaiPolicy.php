<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;

/**
 * Otorisasi CRUD master pegawai.
 *
 * Ability `view`/`viewAny` tetap diwariskan dari LaporanPolicy supaya anti-IDOR
 * laporan tidak berubah. CRUD memakai ability `managePegawai` agar aturan
 * "pegawai hanya melihat datanya sendiri" tidak tertimpa aturan admin-only.
 */
class PegawaiPolicy extends LaporanPolicy
{
    public function managePegawai(User $user, ?Pegawai $pegawai = null): bool
    {
        return $user->role === 'admin';
    }
}
