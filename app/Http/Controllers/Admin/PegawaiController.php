<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Departemen\CariDepartemenAction;
use App\Actions\Pegawai\CariPegawaiAction;
use App\Actions\Pegawai\CreatePegawaiAction;
use App\Actions\Pegawai\DeletePegawaiAction;
use App\Actions\Pegawai\UpdatePegawaiAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePegawaiRequest;
use App\Http\Requests\UpdatePegawaiRequest;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PegawaiController extends Controller
{
    public function index(Request $request, CariPegawaiAction $cari): View
    {
        $this->authorize('managePegawai', Pegawai::class);

        $kataKunci = $request->string('q')->toString();

        return view('admin.pegawai.index', [
            'pegawai' => $cari($kataKunci),
            'kataKunci' => $kataKunci,
        ]);
    }

    public function create(CariDepartemenAction $cari): View
    {
        $this->authorize('managePegawai', Pegawai::class);

        return view('admin.pegawai.create', ['departemen' => $cari()]);
    }

    public function store(StorePegawaiRequest $request, CreatePegawaiAction $create): RedirectResponse
    {
        $this->authorize('managePegawai', Pegawai::class);

        $hasil = $create($request->validated());

        return redirect()->route('admin.pegawai.index')
            ->with('success', "Pegawai {$hasil['pegawai']->nama} berhasil ditambahkan.")
            ->with('passwordSekali', $hasil['passwordAwal']);
    }

    public function edit(Pegawai $pegawai, CariDepartemenAction $cari): View
    {
        $this->authorize('managePegawai', $pegawai);

        return view('admin.pegawai.edit', [
            'pegawai' => $pegawai,
            'departemen' => $cari(),
        ]);
    }

    public function update(UpdatePegawaiRequest $request, Pegawai $pegawai, UpdatePegawaiAction $update): RedirectResponse
    {
        $this->authorize('managePegawai', $pegawai);

        $pegawai = $update($pegawai, $request->validated());

        return redirect()->route('admin.pegawai.index')
            ->with('success', "Pegawai {$pegawai->nama} berhasil diperbarui.");
    }

    public function destroy(Pegawai $pegawai, DeletePegawaiAction $delete): RedirectResponse
    {
        $this->authorize('managePegawai', $pegawai);

        if (! $delete($pegawai)) {
            return redirect()->route('admin.pegawai.index')
                ->with('error', "Pegawai {$pegawai->nama} tidak bisa dihapus karena akun pengguna tidak ditemukan.");
        }

        return redirect()->route('admin.pegawai.index')
            ->with('success', "Pegawai {$pegawai->nama} berhasil dihapus.");
    }
}
