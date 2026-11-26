<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHariLiburRequest;
use App\Http\Requests\UpdateHariLiburRequest;
use App\Models\HariLibur;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HariLiburController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', HariLibur::class);

        return view('admin.hari-libur.index', [
            'hariLibur' => HariLibur::query()->orderBy('tanggal')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', HariLibur::class);

        return view('admin.hari-libur.create');
    }

    public function store(StoreHariLiburRequest $request): RedirectResponse
    {
        $this->authorize('create', HariLibur::class);

        $hariLibur = HariLibur::query()->create($request->validated());

        return redirect()->route('admin.hari-libur.index')
            ->with('success', "Hari libur {$hariLibur->nama} berhasil ditambahkan.");
    }

    public function edit(HariLibur $hariLibur): View
    {
        $this->authorize('update', $hariLibur);

        return view('admin.hari-libur.edit', ['hariLibur' => $hariLibur]);
    }

    public function update(UpdateHariLiburRequest $request, HariLibur $hariLibur): RedirectResponse
    {
        $this->authorize('update', $hariLibur);

        $hariLibur->update($request->validated());

        return redirect()->route('admin.hari-libur.index')
            ->with('success', "Hari libur {$hariLibur->nama} berhasil diperbarui.");
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $this->authorize('delete', $hariLibur);

        $nama = $hariLibur->nama;
        $hariLibur->delete();

        return redirect()->route('admin.hari-libur.index')
            ->with('success', "Hari libur {$nama} berhasil dihapus.");
    }
}
