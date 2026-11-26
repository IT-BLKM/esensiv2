<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Pengaturan\AmbilLokasiKantorAction;
use App\Actions\Presensi\CariMonitoringKehadiranAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\MonitoringKehadiranRequest;
use App\Models\Departemen;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

class MonitoringController extends Controller
{
    /**
     * Monitoring kehadiran satu tanggal + titik koordinat masuk/keluar
     * (issue #41). Filter tanggal, departemen, dan status divalidasi di
     * FormRequest; query ada di Action.
     */
    public function __invoke(
        MonitoringKehadiranRequest $request,
        CariMonitoringKehadiranAction $cari,
        AmbilLokasiKantorAction $ambilLokasiKantor,
    ): View {
        $this->authorize('viewAny', Presensi::class);

        $lokasi = $ambilLokasiKantor();
        $zonaWaktu = (string) config('presensi.zona_waktu', 'Asia/Jakarta');
        $filter = $request->validated();
        $tanggal = $filter['tanggal'] ?? CarbonImmutable::now($zonaWaktu)->toDateString();
        $idDept = isset($filter['id_dept']) ? (int) $filter['id_dept'] : null;
        $status = $filter['status'] ?? null;

        $hasil = $cari($tanggal, $idDept, $status);

        return view('admin.monitoring', [
            'tanggal' => $tanggal,
            'idDept' => $idDept,
            'status' => $status,
            'baris' => $hasil['baris'],
            'titik' => $hasil['titik'],
            'ringkasan' => $hasil['ringkasan'],
            'daftarDepartemen' => Departemen::query()->orderBy('nama')->get(['id', 'kode', 'nama']),
            'labelTanggal' => CarbonImmutable::parse($tanggal)
                ->locale('id')
                ->translatedFormat('l, d F Y'),
            'kantor' => [
                'latitude' => (float) $lokasi['latitude'],
                'longitude' => (float) $lokasi['longitude'],
                'radius' => $lokasi['radius'],
            ],
        ]);
    }
}
