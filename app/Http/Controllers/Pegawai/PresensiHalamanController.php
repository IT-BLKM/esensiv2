<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Laporan\RekapKehadiranAction;
use App\Actions\Pengajuan\CariPengajuanDlHariIniAction;
use App\Actions\Pengaturan\AmbilLokasiKantorAction;
use App\Http\Controllers\Controller;
use App\Models\TukinPeriode;
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
        AmbilLokasiKantorAction $ambilLokasiKantor,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;
        $lokasi = $ambilLokasiKantor();

        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));

        return view('pegawai.presensi', [
            'hariIni' => $pegawai === null ? null : $rekap->hariIni($pegawai),
            'pengajuanDl' => $pegawai === null ? null : $cariDlHariIni($pegawai, $sekarang),
            'periodeTerkunci' => TukinPeriode::terkunci($sekarang->year, $sekarang->month),
            'kantor' => [
                'latitude' => (float) $lokasi['latitude'],
                'longitude' => (float) $lokasi['longitude'],
                'radius' => $lokasi['radius'],
            ],
        ]);
    }
}
