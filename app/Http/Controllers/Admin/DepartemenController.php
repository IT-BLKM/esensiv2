<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Departemen\CariDepartemenAction;
use App\Actions\Departemen\CreateDepartemenAction;
use App\Actions\Departemen\DeleteDepartemenAction;
use App\Actions\Departemen\UpdateDepartemenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartemenRequest;
use App\Http\Requests\UpdateDepartemenRequest;
use App\Models\Departemen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartemenController extends Controller
{
    public function index(Request $request, CariDepartemenAction $cari): View
    {
        $this->authorize('viewAny', Departemen::class);

        $kataKunci = $request->string('q')->toString();

        return view('admin.departemen.index', [
            'departemen' => $cari($kataKunci),
            'kataKunci' => $kataKunci,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Departemen::class);

        return view('admin.departemen.create');
    }

    public function store(StoreDepartemenRequest $request, CreateDepartemenAction $create): RedirectResponse
    {
        $this->authorize('create', Departemen::class);

        $departemen = $create($request->validated());

        return redirect()->route('admin.departemen.index')
            ->with('success', "Departemen {$departemen->nama} berhasil ditambahkan.");
    }

    public function edit(Departemen $departemen): View
    {
        $this->authorize('update', $departemen);

        return view('admin.departemen.edit', ['departemen' => $departemen]);
    }

    public function update(UpdateDepartemenRequest $request, Departemen $departemen, UpdateDepartemenAction $update): RedirectResponse
    {
        $this->authorize('update', $departemen);

        $departemen = $update($departemen, $request->validated());

        return redirect()->route('admin.departemen.index')
            ->with('success', "Departemen {$departemen->nama} berhasil diperbarui.");
    }

    public function destroy(Departemen $departemen, DeleteDepartemenAction $delete): RedirectResponse
    {
        $this->authorize('delete', $departemen);

        if (! $delete($departemen)) {
            return redirect()->route('admin.departemen.index')
                ->with('error', "Departemen {$departemen->nama} tidak bisa dihapus karena masih digunakan pegawai.");
        }

        return redirect()->route('admin.departemen.index')
            ->with('success', "Departemen {$departemen->nama} berhasil dihapus.");
    }
}
