<?php

namespace App\Http\Controllers\Admin;

use App\Actions\JamKerja\CariJamKerjaAction;
use App\Actions\JamKerja\CreateJamKerjaAction;
use App\Actions\JamKerja\DeleteJamKerjaAction;
use App\Actions\JamKerja\UpdateJamKerjaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJamKerjaRequest;
use App\Http\Requests\UpdateJamKerjaRequest;
use App\Models\JamKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JamKerjaController extends Controller
{
    public function index(Request $request, CariJamKerjaAction $cari): View
    {
        $this->authorize('viewAny', JamKerja::class);

        $kataKunci = $request->string('q')->toString();

        return view('admin.jam-kerja.index', [
            'jamKerja' => $cari($kataKunci),
            'kataKunci' => $kataKunci,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', JamKerja::class);

        return view('admin.jam-kerja.create');
    }

    public function store(StoreJamKerjaRequest $request, CreateJamKerjaAction $create): RedirectResponse
    {
        $this->authorize('create', JamKerja::class);

        $jamKerja = $create($request->validated());

        return redirect()->route('admin.jam-kerja.index')
            ->with('success', "Jam kerja {$jamKerja->nama} berhasil ditambahkan.");
    }

    public function edit(JamKerja $jamKerja): View
    {
        $this->authorize('update', $jamKerja);

        return view('admin.jam-kerja.edit', ['jamKerja' => $jamKerja]);
    }

    public function update(UpdateJamKerjaRequest $request, JamKerja $jamKerja, UpdateJamKerjaAction $update): RedirectResponse
    {
        $this->authorize('update', $jamKerja);

        $jamKerja = $update($jamKerja, $request->validated());

        return redirect()->route('admin.jam-kerja.index')
            ->with('success', "Jam kerja {$jamKerja->nama} berhasil diperbarui.");
    }

    public function destroy(JamKerja $jamKerja, DeleteJamKerjaAction $delete): RedirectResponse
    {
        $this->authorize('delete', $jamKerja);

        if (! $delete($jamKerja)) {
            return redirect()->route('admin.jam-kerja.index')
                ->with('error', "Jam kerja {$jamKerja->nama} tidak bisa dihapus karena masih digunakan konfigurasi atau presensi.");
        }

        return redirect()->route('admin.jam-kerja.index')
            ->with('success', "Jam kerja {$jamKerja->nama} berhasil dihapus.");
    }
}
