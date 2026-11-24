<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Cuti\CariCutiAction;
use App\Actions\Cuti\CreateCutiAction;
use App\Actions\Cuti\DeleteCutiAction;
use App\Actions\Cuti\UpdateCutiAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCutiRequest;
use App\Http\Requests\UpdateCutiRequest;
use App\Models\Cuti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CutiController extends Controller
{
    public function index(Request $request, CariCutiAction $cari): View
    {
        $this->authorize('viewAny', Cuti::class);

        $kataKunci = $request->string('q')->toString();

        return view('admin.cuti.index', [
            'cuti' => $cari($kataKunci),
            'kataKunci' => $kataKunci,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Cuti::class);

        return view('admin.cuti.create');
    }

    public function store(StoreCutiRequest $request, CreateCutiAction $create): RedirectResponse
    {
        $this->authorize('create', Cuti::class);

        $cuti = $create($request->validated());

        return redirect()->route('admin.cuti.index')
            ->with('success', "Jenis cuti {$cuti->nama} berhasil ditambahkan.");
    }

    public function edit(Cuti $cuti): View
    {
        $this->authorize('update', $cuti);

        return view('admin.cuti.edit', ['cuti' => $cuti]);
    }

    public function update(UpdateCutiRequest $request, Cuti $cuti, UpdateCutiAction $update): RedirectResponse
    {
        $this->authorize('update', $cuti);

        $cuti = $update($cuti, $request->validated());

        return redirect()->route('admin.cuti.index')
            ->with('success', "Jenis cuti {$cuti->nama} berhasil diperbarui.");
    }

    public function destroy(Cuti $cuti, DeleteCutiAction $delete): RedirectResponse
    {
        $this->authorize('delete', $cuti);

        if (! $delete($cuti)) {
            return redirect()->route('admin.cuti.index')
                ->with('error', "Jenis cuti {$cuti->nama} tidak bisa dihapus karena masih digunakan pengajuan atau saldo.");
        }

        return redirect()->route('admin.cuti.index')
            ->with('success', "Jenis cuti {$cuti->nama} berhasil dihapus.");
    }
}
