<x-layouts.admin title="Pegawai">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Pegawai</h1>
                <p class="mt-1 text-sm text-gray-600">Kelola data pegawai, akun login, departemen, dan foto profil.</p>
            </div>
            <a
                href="{{ route('admin.pegawai.create') }}"
                class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
            >Tambah pegawai</a>
        </div>

        @if (session('passwordSekali'))
            <x-alert variant="warning" title="Simpan password ini sekarang">
                <code class="rounded bg-white px-2 py-1 font-mono text-sm">{{ session('passwordSekali') }}</code>
                <p class="mt-1">Password awal/hasil reset hanya ditampilkan sekali dan tidak dapat dilihat lagi setelah halaman ini dimuat ulang.</p>
            </x-alert>
        @endif

        <x-card>
            <form method="GET" action="{{ route('admin.pegawai.index') }}" class="flex flex-wrap items-end gap-2">
                <div class="w-full sm:w-72">
                    <label for="q" class="block text-sm font-medium text-gray-700">Cari pegawai</label>
                    <x-input
                        id="q"
                        name="q"
                        :value="$kataKunci"
                        placeholder="NIP, nama, atau jabatan"
                        class="mt-1"
                    />
                </div>
                <x-button type="submit" variant="secondary">Cari</x-button>
                @if ($kataKunci !== '')
                    <a
                        href="{{ route('admin.pegawai.index') }}"
                        class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Reset</a>
                @endif
            </form>
        </x-card>

        <x-card>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>NIP</x-table.th>
                        <x-table.th>Nama</x-table.th>
                        <x-table.th>Jabatan</x-table.th>
                        <x-table.th>Departemen</x-table.th>
                        <x-table.th>No. HP</x-table.th>
                        <x-table.th align="center">Foto</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pegawai as $item)
                        <tr>
                            <x-table.td>{{ $item->nip }}</x-table.td>
                            <x-table.td>{{ $item->nama }}</x-table.td>
                            <x-table.td>{{ $item->jabatan }}</x-table.td>
                            <x-table.td>{{ $item->departemen?->nama ?? '—' }}</x-table.td>
                            <x-table.td>{{ $item->no_hp }}</x-table.td>
                            <x-table.td align="center">
                                @if ($item->foto)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::url($item->foto) }}"
                                        alt="Foto {{ $item->nama }}"
                                        class="mx-auto h-10 w-10 rounded-full object-cover"
                                    >
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </x-table.td>
                            <x-table.td align="right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.pegawai.password.edit', $item) }}"
                                        class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                    >Password</a>
                                    <a
                                        href="{{ route('admin.pegawai.edit', $item) }}"
                                        class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                    >Ubah</a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.pegawai.destroy', $item) }}"
                                        onsubmit="return confirm(@js('Hapus pegawai '.$item->nama.'?'));"
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
                            <x-table.td colspan="7">Belum ada pegawai yang cocok.</x-table.td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
            <p class="mt-3 text-xs text-gray-600">Menampilkan {{ $pegawai->count() }} pegawai.</p>
        </x-card>
    </div>
</x-layouts.admin>
