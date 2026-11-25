<x-layouts.admin title="Monitoring">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Monitoring kehadiran</h1>
                <p class="mt-1 text-sm text-gray-600">Kehadiran {{ $labelTanggal }} beserta titik koordinat absen masuk dan pulang.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span> Masuk</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-accent-600"></span> Pulang</span>
            </div>
        </div>

        <x-card title="Filter" subtitle="Saring berdasarkan tanggal, departemen, atau status presensi.">
            <form method="GET" action="{{ route('admin.monitoring.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="lg:flex-1">
                    <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                    <x-input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        :value="$tanggal"
                        :invalid="$errors->has('tanggal')"
                        class="mt-1"
                    />
                    @error('tanggal')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:flex-1">
                    <label for="id_dept" class="block text-sm font-medium text-gray-700">Departemen</label>
                    <select
                        id="id_dept"
                        name="id_dept"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="">Semua departemen</option>
                        @foreach ($daftarDepartemen as $departemen)
                            <option value="{{ $departemen->id }}" @selected($idDept === $departemen->id)>
                                {{ $departemen->kode }} — {{ $departemen->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_dept')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:flex-1">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="">Semua status</option>
                        @foreach (\App\Models\Presensi::LABEL_STATUS as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($status === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" size="sm">Tampilkan</x-button>
                    @if (request()->hasAny(['tanggal', 'id_dept', 'status']))
                        <a
                            href="{{ route('admin.monitoring.index') }}"
                            class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                        >Reset</a>
                    @endif
                </div>
            </form>
        </x-card>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            <x-card>
                <p class="text-xs text-gray-600">Total baris</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['total'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Hadir</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['hadir'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Cuti</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['cuti'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Sakit</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['sakit'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Dinas Luar</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['dl'] }}</p>
            </x-card>
        </div>

        <x-card title="Peta koordinat" subtitle="Klik tombol Peta pada baris untuk memfokuskan titik pegawai.">
            <div
                id="peta-monitoring"
                class="h-80 w-full rounded-md border border-gray-200 bg-gray-100"
                role="img"
                aria-label="Peta koordinat absen masuk dan pulang pegawai"
            ></div>
            <p id="pesan-peta-monitoring" class="mt-2 hidden text-sm text-red-600" role="alert"></p>
            <p class="mt-2 text-xs text-gray-500">Peta memerlukan internet untuk memuat ubin OpenStreetMap. Data tabel tetap tersedia tanpa peta.</p>
        </x-card>

        <div class="flex items-baseline justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">Daftar kehadiran</h2>
            <p class="text-xs text-gray-600">{{ count($baris) }} baris</p>
        </div>

        @if ($baris === [])
            <x-alert variant="info">
                Tidak ada baris presensi yang cocok dengan filter ini.
            </x-alert>
        @else
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Pegawai</x-table.th>
                        <x-table.th>Departemen</x-table.th>
                        <x-table.th>Shift</x-table.th>
                        <x-table.th align="center">Masuk</x-table.th>
                        <x-table.th align="center">Pulang</x-table.th>
                        <x-table.th align="center">Status</x-table.th>
                        <x-table.th>Koordinat</x-table.th>
                        <x-table.th align="center">Foto</x-table.th>
                        <x-table.th align="right">Peta</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($baris as $item)
                        @php
                            $variantStatus = match ($item['status']) {
                                'hadir' => 'success',
                                'cuti' => 'info',
                                'sakit' => 'warning',
                                'dl' => 'brand',
                                default => 'neutral',
                            };

                            $adaKoordinat = $item['koordinatMasuk'] !== null || $item['koordinatKeluar'] !== null;
                        @endphp

                        <tr>
                            <x-table.td>
                                <span class="font-medium text-gray-900">{{ $item['nama'] }}</span>
                                <span class="block text-xs text-gray-600">{{ $item['nip'] }}</span>
                            </x-table.td>
                            <x-table.td>{{ $item['departemen'] ?? '—' }}</x-table.td>
                            <x-table.td>{{ $item['shift'] ?? '—' }}</x-table.td>
                            <x-table.td align="center">
                                @if ($item['jamMasuk'] !== null)
                                    <span class="font-medium text-gray-900">{{ $item['jamMasuk'] }}</span>
                                    @if ($item['menitTerlambat'] > 0)
                                        <span class="block text-xs text-amber-700">Terlambat {{ $item['menitTerlambat'] }} menit</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </x-table.td>
                            <x-table.td align="center">
                                @if ($item['jamKeluar'] !== null)
                                    <span class="font-medium text-gray-900">{{ $item['jamKeluar'] }}</span>
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </x-table.td>
                            <x-table.td align="center">
                                <x-badge :variant="$variantStatus">{{ $item['statusLabel'] }}</x-badge>
                            </x-table.td>
                            <x-table.td>
                                <span class="block text-xs text-gray-600">
                                    Masuk: {{ $item['koordinatMasuk']['teks'] ?? '—' }}
                                </span>
                                <span class="block text-xs text-gray-600">
                                    Pulang: {{ $item['koordinatKeluar']['teks'] ?? '—' }}
                                </span>
                            </x-table.td>
                            <x-table.td align="center">
                                <div class="flex flex-col items-center gap-1 text-xs">
                                    @if ($item['fotoMasuk'] !== null)
                                        <a
                                            href="{{ route('pegawai.presensi.foto', [$item['id'], 'masuk']) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
                                        >Masuk</a>
                                    @endif
                                    @if ($item['fotoKeluar'] !== null)
                                        <a
                                            href="{{ route('pegawai.presensi.foto', [$item['id'], 'keluar']) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
                                        >Pulang</a>
                                    @endif
                                    @if ($item['fotoMasuk'] === null && $item['fotoKeluar'] === null)
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td align="right">
                                <x-button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    data-fokus-presensi="{{ $item['id'] }}"
                                    :disabled="! $adaKoordinat"
                                    title="{{ $adaKoordinat ? 'Tampilkan titik di peta' : 'Tidak ada koordinat untuk baris ini' }}"
                                >Peta</x-button>
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        @endif
    </div>

    <div
        id="halaman-monitoring"
        data-kantor-latitude="{{ $kantor['latitude'] }}"
        data-kantor-longitude="{{ $kantor['longitude'] }}"
        data-kantor-radius="{{ $kantor['radius'] }}"
    ></div>

    <script type="application/json" id="data-monitoring">@json($titik)</script>

    @push('scripts')
        @vite('resources/js/monitoring.js')
    @endpush
</x-layouts.admin>
