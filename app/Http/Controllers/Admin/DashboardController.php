<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Laporan\RekapKehadiranAction;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard admin memakai RekapKehadiranAction yang sama dengan halaman
     * rekap agar angka keterlambatan tidak berbeda (issue #44).
     */
    public function __invoke(RekapKehadiranAction $rekap): View
    {
        $sekarang = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'));
        $bulanan = $rekap->semuaBulanan($sekarang->year, $sekarang->month);
        $tahunan = $rekap->semuaTahunan($sekarang->year);

        $terlambat = array_values(array_filter(
            $bulanan['baris'],
            fn (array $item): bool => $item['hariTerlambat'] > 0,
        ));

        return view('admin.dashboard', [
            'bulan' => $sekarang->month,
            'tahun' => $sekarang->year,
            'labelBulan' => $bulanan['labelPeriode'],
            'ringkasanBulan' => $bulanan['ringkasan'],
            'ringkasanTahun' => $tahunan['ringkasan'],
            'terlambatBulanIni' => array_slice($terlambat, 0, 5),
        ]);
    }
}
