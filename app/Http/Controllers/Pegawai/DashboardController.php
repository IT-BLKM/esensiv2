<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Laporan\RekapKehadiranAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard pegawai (issue #34). Seluruh perhitungan rekap ada di
     * RekapKehadiranAction; controller hanya mengambil pegawai milik user
     * yang login lalu mengirim hasilnya ke view.
     */
    public function __invoke(Request $request, RekapKehadiranAction $rekap): View
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        return view('pegawai.dashboard', [
            'rekap' => $pegawai === null ? null : $rekap($pegawai),
        ]);
    }
}
