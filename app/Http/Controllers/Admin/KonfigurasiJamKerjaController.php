<?php

namespace App\Http\Controllers\Admin;

use App\Actions\KonfigurasiJamKerja\UpdateKonfigurasiJamKerjaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateKonfigurasiJamKerjaRequest;
use App\Models\JamKerja;
use App\Models\KonfigurasiJamKerja;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KonfigurasiJamKerjaController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', KonfigurasiJamKerja::class);

        return view('admin.konfigurasi.index', [
            'daftarHari' => KonfigurasiJamKerja::HARI,
            'daftarPegawai' => Pegawai::query()
                ->with('konfigurasiJamKerja.jamKerja')
                ->orderBy('nama')
                ->get(),
        ]);
    }

    public function edit(Pegawai $pegawai): View
    {
        $this->authorize('update', KonfigurasiJamKerja::class);

        return view('admin.konfigurasi.edit', [
            'daftarHari' => KonfigurasiJamKerja::HARI,
            'pegawai' => $pegawai,
            'jadwal' => $pegawai->konfigurasiJamKerja()->with('jamKerja')->get()->keyBy('hari'),
            'daftarJamKerja' => JamKerja::query()->orderBy('kode')->get(),
        ]);
    }

    public function update(
        UpdateKonfigurasiJamKerjaRequest $request,
        Pegawai $pegawai,
        UpdateKonfigurasiJamKerjaAction $update,
    ): RedirectResponse {
        $this->authorize('update', KonfigurasiJamKerja::class);

        $jumlahHari = $update($pegawai, $request->validated()['jadwal']);

        return redirect()->route('admin.konfigurasi.edit', $pegawai)
            ->with('success', "Jadwal jam kerja {$pegawai->nama} berhasil disimpan ({$jumlahHari} hari).");
    }
}
