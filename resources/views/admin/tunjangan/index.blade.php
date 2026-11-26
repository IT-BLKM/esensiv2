<x-layouts.admin title="Aturan TUKIN">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Aturan tingkat potongan TUKIN</h1>
            <p class="mt-1 text-sm text-gray-600">
                Ubah batas menit dan persentase potongan. Perubahan langsung dipakai perhitungan TUKIN
                setelah disimpan.
            </p>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('admin.tunjangan.update') }}" class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            @foreach ([
                ['judul' => 'Keterlambatan (TL)', 'daftar' => $keterlambatan],
                ['judul' => 'Pulang sebelum waktu (PSW)', 'daftar' => $pulangCepat],
            ] as $grup)
                <x-card :title="$grup['judul']" subtitle="Batas menit bersambung; kosongkan batas atas untuk tingkat terakhir.">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr>
                                    <th class="bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Level</th>
                                    <th class="bg-gray-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Min (menit)</th>
                                    <th class="bg-gray-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Max (menit)</th>
                                    <th class="bg-gray-50 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">Potongan (%)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($grup['daftar'] as $item)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->level }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <input
                                                type="number"
                                                name="tingkat[{{ $item->id }}][min]"
                                                min="0"
                                                value="{{ old("tingkat.{$item->id}.min", $item->min) }}"
                                                class="w-28 rounded-md border border-gray-300 px-3 py-2 text-center text-sm"
                                            >
                                            @error("tingkat.{$item->id}.min")
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <input
                                                type="number"
                                                name="tingkat[{{ $item->id }}][max]"
                                                min="0"
                                                value="{{ old("tingkat.{$item->id}.max", $item->max) }}"
                                                placeholder="Tanpa batas"
                                                class="w-28 rounded-md border border-gray-300 px-3 py-2 text-center text-sm"
                                            >
                                            @error("tingkat.{$item->id}.max")
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                name="tingkat[{{ $item->id }}][persen]"
                                                value="{{ old("tingkat.{$item->id}.persen", $item->persen) }}"
                                                class="w-28 rounded-md border border-gray-300 px-3 py-2 text-center text-sm"
                                            >
                                            @error("tingkat.{$item->id}.persen")
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endforeach

            <div class="flex items-center gap-3">
                <x-button type="submit">Simpan aturan</x-button>
                <p class="text-xs text-gray-500">Perubahan berlaku untuk perhitungan TUKIN berikutnya.</p>
            </div>
        </form>
    </div>
</x-layouts.admin>
