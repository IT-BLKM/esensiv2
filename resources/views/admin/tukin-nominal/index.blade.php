<x-layouts.admin title="Nominal TUKIN">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Nominal TUKIN</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Opsional. Isi nominal dasar per jabatan agar laporan TUKIN menampilkan potongan rupiah.
                    Jabatan yang tidak diisi tetap memakai persentase saja.
                </p>
            </div>
            <a
                href="{{ route('admin.tukin-nominal.create') }}"
                class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
            >Tambah nominal</a>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        <x-card>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Jabatan</x-table.th>
                        <x-table.th align="right">Nominal dasar</x-table.th>
                        <x-table.th>Keterangan</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($nominal as $item)
                        <tr>
                            <x-table.td>{{ $item->jabatan }}</x-table.td>
                            <x-table.td align="right">Rp {{ number_format($item->nominal, 0, ',', '.') }}</x-table.td>
                            <x-table.td>{{ $item->keterangan ?? '—' }}</x-table.td>
                            <x-table.td align="right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.tukin-nominal.edit', $item) }}"
                                        class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                    >Ubah</a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.tukin-nominal.destroy', $item) }}"
                                        onsubmit="return confirm(@js('Hapus nominal '.$item->jabatan.'?'));"
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
                            <x-table.td colspan="4">
                                Belum ada nominal. TUKIN tetap berjalan dengan persentase saja.
                            </x-table.td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
        </x-card>
    </div>
</x-layouts.admin>
