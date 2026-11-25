<x-layouts.pegawai title="Pengajuan">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Pengajuan</h1>
                <p class="mt-1 text-sm text-gray-600">Daftar pengajuan cuti, sakit, dan dinas luar milik Anda.</p>
            </div>

            @if ($adaPegawai)
                <a
                    href="{{ route('pengajuan.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-brand-500 px-4 py-2 text-sm font-medium whitespace-nowrap text-gray-900 transition-colors hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-800"
                >
                    Buat pengajuan
                </a>
            @endif
        </div>

        @if (! $adaPegawai)
            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai, jadi daftar pengajuan belum bisa ditampilkan.
                Hubungi administrator untuk melengkapi data.
            </x-alert>
        @else
            <x-card title="Filter" subtitle="Saring berdasarkan status atau periode pengajuan.">
                <form method="GET" action="{{ route('pengajuan.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="sm:flex-1">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select
                            id="status"
                            name="status"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                            <option value="">Semua status</option>
                            <option value="pending" @selected($status === 'pending')>Menunggu</option>
                            <option value="approved" @selected($status === 'approved')>Disetujui</option>
                            <option value="rejected" @selected($status === 'rejected')>Ditolak</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:flex-1">
                        <label for="bulan" class="block text-sm font-medium text-gray-700">Bulan</label>
                        <select
                            id="bulan"
                            name="bulan"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                            <option value="">Semua bulan</option>
                            @foreach ($pilihanBulan as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($bulan === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('bulan')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:flex-1">
                        <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                        <select
                            id="tahun"
                            name="tahun"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                            <option value="">Semua tahun</option>
                            @foreach ($pilihanTahun as $nilai)
                                <option value="{{ $nilai }}" @selected($tahun === $nilai)>{{ $nilai }}</option>
                            @endforeach
                        </select>
                        @error('tahun')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-button type="submit" variant="primary" size="sm">Tampilkan</x-button>
                </form>
            </x-card>

            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-900">Daftar pengajuan</h2>
                <p class="text-xs text-gray-600">{{ $pengajuan->count() }} pengajuan</p>
            </div>

            @if ($pengajuan->isEmpty())
                <x-alert variant="info">
                    Belum ada pengajuan yang cocok dengan filter ini.
                    <a href="{{ route('pengajuan.create') }}" class="font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800">
                        Buat pengajuan baru
                    </a>.
                </x-alert>
            @else
                <x-table>
                    <thead>
                        <tr>
                            <x-table.th>Tanggal</x-table.th>
                            <x-table.th>Jenis</x-table.th>
                            <x-table.th align="center">Status</x-table.th>
                            <x-table.th align="center">Lampiran</x-table.th>
                            <x-table.th align="right">Aksi</x-table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pengajuan as $item)
                            @php
                                $labelStatus = match ($item->status) {
                                    'pending' => 'Menunggu',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                    default => ucfirst($item->status),
                                };

                                $variantStatus = match ($item->status) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning',
                                };

                                $labelJenis = match ($item->jenis) {
                                    'cuti' => $item->cuti?->nama ?? 'Cuti',
                                    'sakit' => 'Sakit',
                                    'dl' => $item->mode_dl === 'setengah' ? 'Dinas Luar (Setengah)' : 'Dinas Luar (Penuh)',
                                    default => ucfirst($item->jenis),
                                };

                                $mulai = \Illuminate\Support\Carbon::parse((string) $item->tanggal_mulai)->locale('id');
                                $akhir = \Illuminate\Support\Carbon::parse((string) $item->tanggal_berakhir)->locale('id');
                            @endphp

                            <tr>
                                <x-table.td>
                                    <span class="font-medium text-gray-900">{{ $mulai->translatedFormat('d M Y') }}</span>
                                    @if ($item->tanggal_mulai !== $item->tanggal_berakhir)
                                        <span class="block text-xs text-gray-600">
                                            s.d. {{ $akhir->translatedFormat('d M Y') }}
                                        </span>
                                    @endif
                                </x-table.td>
                                <x-table.td>{{ $labelJenis }}</x-table.td>
                                <x-table.td align="center">
                                    <x-badge :variant="$variantStatus">{{ $labelStatus }}</x-badge>
                                </x-table.td>
                                <x-table.td align="center">
                                    @if ($item->lampiran !== null)
                                        <x-badge variant="info">Ada</x-badge>
                                    @else
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </x-table.td>
                                <x-table.td align="right">
                                    <a
                                        href="{{ route('pengajuan.show', $item) }}"
                                        class="font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
                                    >
                                        Detail
                                    </a>
                                </x-table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-table>
            @endif
        @endif
    </div>
</x-layouts.pegawai>
