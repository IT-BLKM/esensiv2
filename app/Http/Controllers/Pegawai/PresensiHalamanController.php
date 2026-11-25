<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Laporan\RekapKehadiranAction;
use App\Actions\Pengajuan\CariPengajuanDlHariIniAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresensiHalamanController extends Controller
{
    /**
     * Halaman presensi pegawai. Status masuk/keluar dan shift hari ini
     * berasal dari RekapKehadiranAction; pengajuan DL hari ini dipakai untuk
     * membedakan DL penuh (tidak perlu absen) dan DL setengah (absen sekali).
     */
    public function __invoke(
        Request $request,
        RekapKehadiranAction $rekap,
        CariPengajuanDlHariIniAction $cariDlHariIni,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));

        return view('pegawai.presensi', [
            'hariIni' => $pegawai === null ? null : $rekap->hariIni($pegawai),
            'pengajuanDl' => $pegawai === null ? null : $cariDlHariIni($pegawai, $sekarang),
            'kantor' => [
                'latitude' => (float) config('presensi.latitude'),
                'longitude' => (float) config('presensi.longitude'),
                'radius' => (int) config('presensi.radius'),
            ],
        ]);
    }
}
