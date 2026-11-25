<?php

namespace App\Http\Controllers\Pegawai;

use App\Actions\Cuti\HitungSisaCutiAction;
use App\Actions\Pengajuan\CreatePengajuanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengajuanRequest;
use App\Models\Cuti;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman form pengajuan pegawai (issue #36).
 *
 * Endpoint JSON pegawai.pengajuan.* tetap dipakai oleh histori (#33), jadi
 * controller ini hanya menangani halaman HTML dan pengiriman form.
 */
class PengajuanFormController extends Controller
{
    public function __construct(private readonly HitungSisaCutiAction $hitungSisaCuti) {}

    public function index(Request $request): View
    {
        return $this->form($request);
    }

    public function create(Request $request): View
    {
        return $this->form($request);
    }

    public function store(StorePengajuanRequest $request, CreatePengajuanAction $create): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $create($user, $request->validated());

        return redirect()->route('pengajuan.index')
            ->with('success', 'Pengajuan berhasil dikirim dan menunggu persetujuan.');
    }

    private function form(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $pegawai = $user->pegawai;

        $cuti = Cuti::query()->orderBy('kode')->get(['id', 'kode', 'nama', 'kuota', 'maks_hari']);

        $tahun = CarbonImmutable::now((string) config('presensi.zona_waktu', 'Asia/Jakarta'))->year;
        $oldMulai = $request->old('tanggal_mulai');

        if (is_string($oldMulai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $oldMulai) === 1) {
            $tahun = (int) substr($oldMulai, 0, 4);
        }

        $sisa = [];

        if ($pegawai !== null) {
            foreach ($cuti as $item) {
                $sisa[$item->id] = ($this->hitungSisaCuti)($pegawai, $item, $tahun);
            }
        }

        return view('pegawai.pengajuan', [
            'cuti' => $cuti,
            'sisa' => $sisa,
            'tahunTampil' => $tahun,
            'adaPegawai' => $pegawai !== null,
        ]);
    }
}
