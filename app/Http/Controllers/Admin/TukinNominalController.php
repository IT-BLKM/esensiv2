<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTukinNominalRequest;
use App\Http\Requests\UpdateTukinNominalRequest;
use App\Models\TukinNominal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TukinNominalController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TukinNominal::class);

        return view('admin.tukin-nominal.index', [
            'nominal' => TukinNominal::query()->orderBy('jabatan')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', TukinNominal::class);

        return view('admin.tukin-nominal.create');
    }

    public function store(StoreTukinNominalRequest $request): RedirectResponse
    {
        $this->authorize('create', TukinNominal::class);

        $nominal = TukinNominal::query()->create($request->validated());

        return redirect()->route('admin.tukin-nominal.index')
            ->with('success', "Nominal TUKIN untuk {$nominal->jabatan} berhasil ditambahkan.");
    }

    public function edit(TukinNominal $tukinNominal): View
    {
        $this->authorize('update', $tukinNominal);

        return view('admin.tukin-nominal.edit', ['nominal' => $tukinNominal]);
    }

    public function update(UpdateTukinNominalRequest $request, TukinNominal $tukinNominal): RedirectResponse
    {
        $this->authorize('update', $tukinNominal);

        $tukinNominal->update($request->validated());

        return redirect()->route('admin.tukin-nominal.index')
            ->with('success', "Nominal TUKIN untuk {$tukinNominal->jabatan} berhasil diperbarui.");
    }

    public function destroy(TukinNominal $tukinNominal): RedirectResponse
    {
        $this->authorize('delete', $tukinNominal);

        $jabatan = $tukinNominal->jabatan;
        $tukinNominal->delete();

        return redirect()->route('admin.tukin-nominal.index')
            ->with('success', "Nominal TUKIN untuk {$jabatan} berhasil dihapus.");
    }
}
