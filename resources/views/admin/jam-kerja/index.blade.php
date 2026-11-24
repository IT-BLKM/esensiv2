<x-layouts.admin title="Jam Kerja">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Jam Kerja</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola master shift dan rentang jam masuk serta jam pulang.</p>
            </div>
            <a
                href="{{ route('admin.jam-kerja.create') }}"
                class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
            >Tambah jam kerja</a>
        </div>

        <x-card>
            <form method="GET" action="{{ route('admin.jam-kerja.index') }}" class="flex flex-wrap items-end gap-2">
                <div class="w-full sm:w-72">
                    <label for="q" class="block text-sm font-medium text-gray-700">Cari jam kerja</label>
                    <x-input
                        id="q"
                        name="q"
                        :value="$kataKunci"
                        placeholder="Kode atau nama shift"
                        class="mt-1"
                    />
                </div>
                <x-button type="submit" variant="secondary">Cari</x-button>
                @if ($kataKunci !== '')
                    <a
                        href="{{ route('admin.jam-kerja.index') }}"
                        class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Reset</a>
                @endif
            </form>
        </x-card>

        <x-card>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Kode</x-table.th>
                        <x-table.th>Nama</x-table.th>
                        <x-table.th>Awal masuk</x-table.th>
                        <x-table.th>Jam masuk</x-table.th>
                        <x-table.th>Akhir masuk</x-table.th>
                        <x-table.th>Jam pulang</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($jamKerja as $item)
                        <tr>
                            <x-table.td>{{ $item->kode }}</x-table.td>
                            <x-table.td>{{ $item->nama }}</x-table.td>
                            <x-table.td>{{ substr($item->awal_masuk, 0, 5) }}</x-table.td>
                            <x-table.td>{{ substr($item->jam_masuk, 0, 5) }}</x-table.td>
                            <x-table.td>{{ substr($item->akhir_masuk, 0, 5) }}</x-table.td>
                            <x-table.td>{{ substr($item->jam_pulang, 0, 5) }}</x-table.td>
                            <x-table.td align="right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.jam-kerja.edit', $item) }}"
                                        class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                    >Ubah</a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.jam-kerja.destroy', $item) }}"
                                        onsubmit="return confirm(@js('Hapus jam kerja '.$item->nama.'?'));"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                                    </form>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <x-table.td colspan="7">Belum ada jam kerja yang cocok.</x-table.td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
            <p class="mt-3 text-xs text-gray-600">Menampilkan {{ $jamKerja->count() }} jam kerja.</p>
        </x-card>
    </div>
</x-layouts.admin>
