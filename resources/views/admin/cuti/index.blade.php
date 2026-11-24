<x-layouts.admin title="Cuti">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Jenis &amp; Aturan Cuti</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola kode, nama, batas hari, kuota, dan batas tabungan per jenis cuti.</p>
            </div>
            <a
                href="{{ route('admin.cuti.create') }}"
                class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
            >Tambah jenis cuti</a>
        </div>

        <x-alert variant="info">
            Saldo cuti pegawai disimpan terpisah di <code class="rounded bg-gray-100 px-1">saldo_cuti</code>. Halaman ini hanya mengatur aturan jenis cuti.
        </x-alert>

        <x-card>
            <form method="GET" action="{{ route('admin.cuti.index') }}" class="flex flex-wrap items-end gap-2">
                <div class="w-full sm:w-72">
                    <label for="q" class="block text-sm font-medium text-gray-700">Cari jenis cuti</label>
                    <x-input
                        id="q"
                        name="q"
                        :value="$kataKunci"
                        placeholder="Kode atau nama cuti"
                        class="mt-1"
                    />
                </div>
                <x-button type="submit" variant="secondary">Cari</x-button>
                @if ($kataKunci !== '')
                    <a
                        href="{{ route('admin.cuti.index') }}"
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
                        <x-table.th align="center">Maks hari</x-table.th>
                        <x-table.th align="center">Kuota</x-table.th>
                        <x-table.th align="center">Maks bawa</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cuti as $item)
                        <tr>
                            <x-table.td>{{ $item->kode }}</x-table.td>
                            <x-table.td>{{ $item->nama }}</x-table.td>
                            <x-table.td align="center">{{ $item->maks_hari ?? '—' }}</x-table.td>
                            <x-table.td align="center">{{ $item->kuota ?? '—' }}</x-table.td>
                            <x-table.td align="center">{{ $item->maks_bawa }}</x-table.td>
                            <x-table.td align="right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.cuti.edit', $item) }}"
                                        class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                    >Ubah</a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.cuti.destroy', $item) }}"
                                        onsubmit="return confirm(@js('Hapus jenis cuti '.$item->nama.'?'));"
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
                            <x-table.td colspan="6">Belum ada jenis cuti yang cocok.</x-table.td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
            <p class="mt-3 text-xs text-gray-600">Menampilkan {{ $cuti->count() }} jenis cuti.</p>
        </x-card>
    </div>
</x-layouts.admin>
