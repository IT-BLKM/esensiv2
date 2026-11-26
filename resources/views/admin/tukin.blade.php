<x-layouts.admin title="TUKIN">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Tunjangan Kinerja (TUKIN)</h1>
            <p class="mt-1 text-sm text-gray-600">
                Rekap potongan dari keterlambatan (TL) dan pulang sebelum waktu (PSW).
                Persentase adalah potongan dari tunjangan kinerja, bukan nominal rupiah.
            </p>
        </div>

        <x-card title="Filter" subtitle="Pilih periode bulanan atau seluruh tahun, lalu pilih pegawai untuk melihat rincian.">
            <form method="GET" action="{{ route('admin.tukin.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="lg:flex-1">
                    <label for="bulan" class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select
                        id="bulan"
                        name="bulan"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="" @selected($bulan === null)>Seluruh tahun</option>
                        @foreach ($pilihanBulan as $nilai => $label)
                            <option value="{{ $nilai }}" @selected($bulan === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('bulan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:flex-1">
                    <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                    <select
                        id="tahun"
                        name="tahun"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        @foreach ($pilihanTahun as $nilai)
                            <option value="{{ $nilai }}" @selected($tahun === $nilai)>{{ $nilai }}</option>
                        @endforeach
                    </select>
                    @error('tahun')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:flex-[2]">
                    <label for="id_pegawai" class="block text-sm font-medium text-gray-700">Pegawai (opsional)</label>
                    <select
                        id="id_pegawai"
                        name="id_pegawai"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="">Semua pegawai</option>
                        @foreach ($daftarPegawai as $item)
                            <option value="{{ $item->id }}" @selected($idPegawai === $item->id)>
                                {{ $item->nama }} ({{ $item->nip }})
                            </option>
                        @endforeach
                    </select>
                    @error('id_pegawai')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <x-button type="submit" variant="primary" size="sm">Tampilkan</x-button>
                    <a
                        href="{{ route('admin.tukin.index') }}"
                        class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Reset</a>
                </div>
            </form>
        </x-card>

        <x-card title="Unduh laporan" subtitle="File mengikuti filter periode dan pegawai di atas.">
            <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <p class="text-sm font-medium text-gray-900">Semua pegawai</p>

                    <form method="GET" action="{{ route('admin.tukin.export.excel') }}" class="inline">
                        <input type="hidden" name="bulan" value="{{ $bulan ?? '' }}">
                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                        <x-button type="submit" variant="secondary" size="sm">Excel semua pegawai</x-button>
                    </form>

                    <form method="GET" action="{{ route('admin.tukin.export.pdf') }}" class="inline">
                        <input type="hidden" name="bulan" value="{{ $bulan ?? '' }}">
                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                        <x-button type="submit" variant="secondary" size="sm">PDF semua pegawai</x-button>
                    </form>
                </div>

                @if ($pegawai !== null)
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm font-medium text-gray-900">{{ $pegawai->nama }}</p>

                        <form method="GET" action="{{ route('admin.tukin.export.excel') }}" class="inline">
                            <input type="hidden" name="id_pegawai" value="{{ $pegawai->id }}">
                            <input type="hidden" name="bulan" value="{{ $bulan ?? '' }}">
                            <input type="hidden" name="tahun" value="{{ $tahun }}">
                            <x-button type="submit" variant="primary" size="sm">Excel pegawai ini</x-button>
                        </form>

                        <form method="GET" action="{{ route('admin.tukin.export.pdf') }}" class="inline">
                            <input type="hidden" name="id_pegawai" value="{{ $pegawai->id }}">
                            <input type="hidden" name="bulan" value="{{ $bulan ?? '' }}">
                            <input type="hidden" name="tahun" value="{{ $tahun }}">
                            <x-button type="submit" variant="primary" size="sm">PDF pegawai ini</x-button>
                        </form>
                    </div>
                @endif
            </div>
        </x-card>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <x-card>
                <p class="text-xs text-gray-600">Pegawai kena potongan</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">
                    {{ $ringkasan['pegawaiKenaPotongan'] }} / {{ $ringkasan['totalPegawai'] }}
                </p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Total potongan TL</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($ringkasan['totalPersenTl'], 2, ',', '.') }}%</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Total potongan PSW</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($ringkasan['totalPersenPsw'], 2, ',', '.') }}%</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Total potongan</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($ringkasan['totalPersen'], 2, ',', '.') }}%</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Hari ada potongan</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['totalHariPotongan'] }}</p>
            </x-card>
        </div>

        @if ($detail !== null && $pegawai !== null)
            <x-card title="Rincian {{ $pegawai->nama }}" subtitle="Sumber potongan per tanggal pada {{ $labelPeriode }}.">
                <div class="mb-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Total % TL</p>
                        <p class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenTl'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Total % PSW</p>
                        <p class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenPsw'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Hari diganti</p>
                        <p class="font-semibold text-gray-900">{{ $detail['ringkasan']['jumlahPenggantian'] }}</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Total potongan</p>
                        <p class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersen'], 2, ',', '.') }}%</p>
                    </div>
                </div>

                @if ($detail['baris'] === [])
                    <x-alert variant="info">Tidak ada potongan TL/PSW pada {{ $labelPeriode }}.</x-alert>
                @else
                    <x-table>
                        <thead>
                            <tr>
                                <x-table.th>Tanggal</x-table.th>
                                <x-table.th>Shift</x-table.th>
                                <x-table.th align="center">Masuk</x-table.th>
                                <x-table.th align="center">Pulang</x-table.th>
                                <x-table.th align="center">TL</x-table.th>
                                <x-table.th align="center">PSW</x-table.th>
                                <x-table.th align="right">Potongan</x-table.th>
                                <x-table.th>Keterangan</x-table.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($detail['baris'] as $item)
                                <tr>
                                    <x-table.td>
                                        <span class="font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse((string) $item['tanggal'])->locale('id')->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="block text-xs text-gray-500">{{ $item['hari'] }}</span>
                                    </x-table.td>
                                    <x-table.td>{{ $item['shift'] }}</x-table.td>
                                    <x-table.td align="center">{{ $item['jamMasuk'] ?? '—' }}</x-table.td>
                                    <x-table.td align="center">{{ $item['jamKeluar'] ?? '—' }}</x-table.td>
                                    <x-table.td align="center">
                                        @if ($item['levelTl'] !== null)
                                            <x-badge variant="danger">{{ $item['levelTl'] }}</x-badge>
                                            <span class="block text-xs text-gray-500">
                                                {{ $item['menitTerlambat'] === null ? 'tanpa jam masuk' : $item['menitTerlambat'].' menit' }}
                                            </span>
                                        @elseif ($item['penggantianTerpenuhi'])
                                            <x-badge variant="success">Diganti</x-badge>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td align="center">
                                        @if ($item['levelPsw'] !== null)
                                            <x-badge variant="warning">{{ $item['levelPsw'] }}</x-badge>
                                            <span class="block text-xs text-gray-500">
                                                {{ $item['menitPulangCepat'] === null ? 'tanpa jam pulang' : $item['menitPulangCepat'].' menit' }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <span class="font-semibold text-gray-900">{{ number_format($item['persenTotal'], 2, ',', '.') }}%</span>
                                    </x-table.td>
                                    <x-table.td>
                                        <span class="text-xs text-gray-600">{{ $item['catatanPenggantian'] ?? '—' }}</span>
                                    </x-table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>
        @endif

        <div class="flex items-baseline justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">Rekap pegawai</h2>
            <p class="text-xs text-gray-600">{{ $labelPeriode }} · {{ count($baris) }} pegawai</p>
        </div>

        <x-card>
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>NIP</x-table.th>
                        <x-table.th>Nama Pegawai</x-table.th>
                        <x-table.th align="center">TL1</x-table.th>
                        <x-table.th align="center">TL2</x-table.th>
                        <x-table.th align="center">TL3</x-table.th>
                        <x-table.th align="center">PSW1</x-table.th>
                        <x-table.th align="center">PSW2</x-table.th>
                        <x-table.th align="center">PSW3</x-table.th>
                        <x-table.th align="center">PSW4</x-table.th>
                        <x-table.th align="right">% TL</x-table.th>
                        <x-table.th align="right">% PSW</x-table.th>
                        <x-table.th align="right">Total</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($baris as $item)
                        @php($r = $item['ringkasan'])
                        <tr class="@if ($r['totalPersen'] > 0) bg-red-50/40 @endif">
                            <x-table.td>{{ $item['nip'] }}</x-table.td>
                            <x-table.td>
                                <div class="font-medium text-gray-900">{{ $item['nama'] }}</div>
                                <div class="text-xs text-gray-500">{{ $item['departemen'] ?? '—' }}</div>
                            </x-table.td>
                            <x-table.td align="center">{{ $r['jumlahTl1'] }}</x-table.td>
                            <x-table.td align="center">{{ $r['jumlahTl2'] }}</x-table.td>
                            <x-table.td align="center">{{ $r['jumlahTl3'] }}</x-table.td>
                            <x-table.td align="center">{{ $r['jumlahPsw1'] }}</x-table.td>
                            <x-table.td align="center">{{ $r['jumlahPsw2'] }}</x-table.td>
                            <x-table.td align="center">{{ $r['jumlahPsw3'] }}</x-table.td>
                            <x-table.td align="center">{{ $r['jumlahPsw4'] }}</x-table.td>
                            <x-table.td align="right">{{ number_format($r['totalPersenTl'], 2, ',', '.') }}%</x-table.td>
                            <x-table.td align="right">{{ number_format($r['totalPersenPsw'], 2, ',', '.') }}%</x-table.td>
                            <x-table.td align="right">
                                <span class="font-semibold text-gray-900">{{ number_format($r['totalPersen'], 2, ',', '.') }}%</span>
                            </x-table.td>
                            <x-table.td align="right">
                                <a
                                    href="{{ route('admin.tukin.index', [
                                        'id_pegawai' => $item['id'],
                                        'bulan' => $bulan ?? '',
                                        'tahun' => $tahun,
                                    ]) }}"
                                    class="rounded-md text-sm font-medium text-brand-700 hover:text-brand-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                                >Rincian</a>
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </x-card>
    </div>
</x-layouts.admin>
