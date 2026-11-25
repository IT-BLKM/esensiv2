<x-layouts.admin title="Pengajuan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pengajuan</h1>
            <p class="mt-1 text-sm text-gray-600">Tinjau dan putuskan pengajuan cuti, sakit, dan dinas luar pegawai.</p>
        </div>

        <x-card title="Filter" subtitle="Default menampilkan pengajuan yang menunggu keputusan.">
            <form method="GET" action="{{ route('admin.pengajuan.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="lg:flex-1">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="" @selected($status === null)>Semua status</option>
                        <option value="pending" @selected($status === 'pending')>Menunggu</option>
                        <option value="approved" @selected($status === 'approved')>Disetujui</option>
                        <option value="rejected" @selected($status === 'rejected')>Ditolak</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:flex-1">
                    <label for="id_pegawai" class="block text-sm font-medium text-gray-700">Pegawai</label>
                    <select
                        id="id_pegawai"
                        name="id_pegawai"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="">Semua pegawai</option>
                        @foreach ($daftarPegawai as $pegawaiItem)
                            <option value="{{ $pegawaiItem->id }}" @selected($idPegawai === $pegawaiItem->id)>
                                {{ $pegawaiItem->nama }} ({{ $pegawaiItem->nip }})
                            </option>
                        @endforeach
                    </select>
                    @error('id_pegawai')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_dari" class="block text-sm font-medium text-gray-700">Dari tanggal</label>
                    <x-input
                        type="date"
                        name="tanggal_dari"
                        id="tanggal_dari"
                        value="{{ $tanggalDari }}"
                        :invalid="$errors->has('tanggal_dari')"
                    />
                    @error('tanggal_dari')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tanggal_sampai" class="block text-sm font-medium text-gray-700">Sampai tanggal</label>
                    <x-input
                        type="date"
                        name="tanggal_sampai"
                        id="tanggal_sampai"
                        value="{{ $tanggalSampai }}"
                        :invalid="$errors->has('tanggal_sampai')"
                    />
                    @error('tanggal_sampai')
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
            <x-alert variant="info">Tidak ada pengajuan yang cocok dengan filter ini.</x-alert>
        @else
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Pegawai</x-table.th>
                        <x-table.th>Tanggal</x-table.th>
                        <x-table.th>Jenis</x-table.th>
                        <x-table.th align="center">Status</x-table.th>
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
                                'dl' => 'Dinas Luar',
                                default => ucfirst($item->jenis),
                            };

                            $mulai = \Illuminate\Support\Carbon::parse((string) $item->tanggal_mulai)->locale('id');
                            $akhir = \Illuminate\Support\Carbon::parse((string) $item->tanggal_berakhir)->locale('id');
                        @endphp

                        <tr>
                            <x-table.td>
                                <span class="font-medium text-gray-900">{{ $item->pegawai?->nama ?? '—' }}</span>
                                <span class="block text-xs text-gray-600">{{ $item->pegawai?->nip }}</span>
                            </x-table.td>
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
                            <x-table.td align="right">
                                <a
                                    href="{{ route('admin.pengajuan.show', $item) }}"
                                    class="font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
                                >
                                    Tinjau
                                </a>
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </div>
</x-layouts.admin>
