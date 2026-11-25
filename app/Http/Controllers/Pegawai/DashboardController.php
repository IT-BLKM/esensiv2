<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Cuti\HitungSisaCutiAction;
use App\Actions\Laporan\RekapKehadiranAction;
use App\Http\Controllers\Controller;
use App\Models\Cuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard pegawai (issue #34). Seluruh perhitungan rekap ada di
     * RekapKehadiranAction; controller hanya mengambil pegawai milik user
     * yang login lalu mengirim hasilnya ke view.
     */
    public function __invoke(
        Request $request,
        RekapKehadiranAction $rekap,
        HitungSisaCutiAction $hitungSisaCuti,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        $sisaCuti = null;

        if ($pegawai !== null) {
            $cutiTahunan = Cuti::query()->where('kode', Cuti::KODE_TAHUNAN)->first();
            $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;

            if ($cutiTahunan !== null) {
                $sisaCuti = $hitungSisaCuti($pegawai, $cutiTahunan, $tahun);
            }
        }

        return view('pegawai.dashboard', [
            'rekap' => $pegawai === null ? null : $rekap($pegawai),
            'sisaCuti' => $sisaCuti,
        ]);
    }
}
