<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTunjanganTingkatRequest;
use App\Models\TunjanganTingkat;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TunjanganTingkatController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TunjanganTingkat::class);
        $this->pastikanDefault();

        return view('admin.tunjangan.index', [
            'keterlambatan' => TunjanganTingkat::query()
                ->where('kategori', TunjanganTingkat::KATEGORI_KETERLAMBATAN)
                ->orderBy('min')
                ->get(),
            'pulangCepat' => TunjanganTingkat::query()
                ->where('kategori', TunjanganTingkat::KATEGORI_PULANG_CEPAT)
                ->orderBy('min')
                ->get(),
        ]);
    }

    public function update(UpdateTunjanganTingkatRequest $request): RedirectResponse
    {
        $this->authorize('updateAny', TunjanganTingkat::class);

        foreach ($request->validated()['tingkat'] as $id => $baris) {
            $tingkat = TunjanganTingkat::query()->find((int) $id);

            if ($tingkat === null) {
                continue;
            }

            $tingkat->update([
                'min' => (int) $baris['min'],
                'max' => $baris['max'] === null || $baris['max'] === '' ? null : (int) $baris['max'],
                'persen' => (float) $baris['persen'],
            ]);
        }

        return redirect()->route('admin.tunjangan.index')
            ->with('success', 'Aturan tingkat potongan TUKIN berhasil disimpan.');
    }

    private function pastikanDefault(): void
    {
        if (TunjanganTingkat::query()->exists()) {
            return;
        }

        foreach ([
            TunjanganTingkat::KATEGORI_KETERLAMBATAN => config('tunjangan.keterlambatan', []),
            TunjanganTingkat::KATEGORI_PULANG_CEPAT => config('tunjangan.pulang_cepat', []),
        ] as $kategori => $daftar) {
            foreach ($daftar as $tingkat) {
                TunjanganTingkat::query()->create([
                    'kategori' => $kategori,
                    'level' => $tingkat['level'],
                    'min' => $tingkat['min'],
                    'max' => $tingkat['max'],
                    'persen' => $tingkat['persen'],
                ]);
            }
        }
    }
}
