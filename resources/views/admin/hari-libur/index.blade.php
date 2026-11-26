<x-layouts.admin title="Hari Libur">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Hari libur nasional</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Tanggal di sini tidak dihitung sebagai hari kerja untuk cuti, sakit, dinas luar, dan presensi.
                </p>
            </div>
            <a
                href="{{ route('admin.hari-libur.create') }}"
                class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
            >Tambah hari libur</a>
        </div>

        @if (session('success'))
            <x-alert variant="success">{{ session('success') }}</x-alert>
        @endif

        @if ($jumlahTahunIni === 0)
            <x-alert variant="warning" title="Belum ada hari libur {{ $tahunIni }}">
                Tambahkan hari libur nasional tahun ini agar cuti/sakit/DL dan laporan tidak
                menghitung tanggal libur sebagai hari kerja.
            </x-alert>
        @endif

        <x-card>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th align="center">No</x-table.th>
                        <x-table.th>Tanggal</x-table.th>
                        <x-table.th>Nama hari libur</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($hariLibur as $item)
                        <tr>
                            <x-table.td align="center">{{ $loop->iteration }}</x-table.td>
                            <x-table.td>
                                <span class="font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse((string) $item->tanggal)->locale('id')->translatedFormat('l, d F Y') }}
                                </span>
                            </x-table.td>
                            <x-table.td>{{ $item->nama }}</x-table.td>
                            <x-table.td align="right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('admin.hari-libur.edit', $item) }}"
                                        class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                    >Ubah</a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.hari-libur.destroy', $item) }}"
                                        onsubmit="return confirm(@js('Hapus hari libur '.$item->nama.'?'));"
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
                            <x-table.td colspan="4">Belum ada hari libur. Tambahkan tanggal libur nasional atau cuti bersama.</x-table.td>
                        </tr>
                    @endforelse
                </tbody>
            </x-table>
            <p class="mt-3 text-xs text-gray-600">Menampilkan {{ $hariLibur->count() }} hari libur.</p>
        </x-card>
    </div>
</x-layouts.admin>
