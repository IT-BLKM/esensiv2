<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Laporan\RekapKehadiranAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresensiHalamanController extends Controller
{
    /**
     * Halaman presensi pegawai (issue #31 — UI saja). Status masuk/keluar dan
     * shift hari ini berasal dari RekapKehadiranAction yang sama dengan
     * dashboard; penyimpanan ke server baru aktif di issue #32.
     */
    public function __invoke(Request $request, RekapKehadiranAction $rekap): View
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        return view('pegawai.presensi', [
            'hariIni' => $pegawai === null ? null : $rekap->hariIni($pegawai),
            'kantor' => [
                'latitude' => (float) config('presensi.latitude'),
                'longitude' => (float) config('presensi.longitude'),
                'radius' => (int) config('presensi.radius'),
            ],
        ]);
    }
}
