<x-layouts.pegawai title="TUKIN">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Potongan TUKIN</h1>
            @if ($pegawai !== null)
                <p class="mt-1 text-sm font-medium text-gray-800">{{ $pegawai->nama }} · {{ $pegawai->nip }}</p>
            @endif
            <p class="mt-1 text-sm text-gray-600">Rincian potongan tunjangan kinerja dari keterlambatan dan pulang sebelum waktu.</p>
        </div>

        @if ($detail === null)
            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai, jadi rincian TUKIN belum bisa ditampilkan.
            </x-alert>
        @else
            <x-card title="Periode" subtitle="Pilih bulan atau seluruh tahun.">
                <form method="GET" action="{{ route('tukin.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="sm:flex-1">
                        <label for="bulan" class="block text-sm font-medium text-gray-700">Bulan</label>
                        <select
                            id="bulan"
                            name="bulan"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                            <option value="" @selected($bulan === null)>Seluruh tahun</option>
                            @foreach ($pilihanBulan as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($bulan === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:flex-1">
                        <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                        <select
                            id="tahun"
                            name="tahun"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                        >
                            @foreach ($pilihanTahun as $nilai)
                                <option value="{{ $nilai }}" @selected($tahun === $nilai)>{{ $nilai }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-button type="submit" size="sm">Tampilkan</x-button>
                </form>
            </x-card>

            @if ($terkunci)
                <x-alert variant="warning">
                    Periode {{ $labelPeriode }} sudah dikunci admin. Data presensi tidak bisa diubah lagi.
                </x-alert>
            @endif

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div class="rounded-md border border-brand-200 bg-brand-50 px-3 py-2">
                    <p class="text-xs text-brand-800">Total potongan</p>
                    <p class="mt-1 text-lg font-semibold text-brand-900">{{ number_format($detail['ringkasan']['totalPersenAkhir'], 2, ',', '.') }}%</p>
                </div>
                <div class="rounded-md border border-gray-200 px-3 py-2">
                    <p class="text-xs text-gray-600">TL</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenTl'], 2, ',', '.') }}%</p>
                </div>
                <div class="rounded-md border border-gray-200 px-3 py-2">
                    <p class="text-xs text-gray-600">PSW</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenPsw'], 2, ',', '.') }}%</p>
                </div>
                <div class="rounded-md border border-gray-200 px-3 py-2">
                    <p class="text-xs text-gray-600">Hari diganti</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $detail['ringkasan']['jumlahPenggantian'] }}</p>
                </div>
            </div>

            <x-card title="Rincian {{ $labelPeriode }}">
                @if ($detail['baris'] === [])
                    <x-alert variant="info">Tidak ada potongan TL/PSW pada periode ini.</x-alert>
                @else
                    <x-table>
                        <thead>
                            <tr>
                                <x-table.th>Tanggal</x-table.th>
                                <x-table.th align="center">Masuk</x-table.th>
                                <x-table.th align="center">Pulang</x-table.th>
                                <x-table.th align="center">TL</x-table.th>
                                <x-table.th align="center">PSW</x-table.th>
                                <x-table.th align="right">Potongan</x-table.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($detail['baris'] as $item)
                                <tr>
                                    <x-table.td>
                                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse((string) $item['tanggal'])->locale('id')->translatedFormat('d M Y') }}</span>
                                        <span class="block text-xs text-gray-500">{{ $item['hari'] }}</span>
                                    </x-table.td>
                                    <x-table.td align="center">{{ $item['jamMasuk'] ?? '—' }}</x-table.td>
                                    <x-table.td align="center">{{ $item['jamKeluar'] ?? '—' }}</x-table.td>
                                    <x-table.td align="center">
                                        @if ($item['levelTl'] !== null)
                                            <x-badge variant="danger">{{ $item['levelTl'] }}</x-badge>
                                            <span class="block text-xs text-gray-500">{{ $item['menitTerlambat'] === null ? 'tanpa jam masuk' : $item['menitTerlambat'].' menit' }}</span>
                                        @elseif ($item['penggantianTerpenuhi'])
                                            <x-badge variant="success">Diganti</x-badge>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td align="center">
                                        @if ($item['levelPsw'] !== null)
                                            <x-badge variant="warning">{{ $item['levelPsw'] }}</x-badge>
                                            <span class="block text-xs text-gray-500">{{ $item['menitPulangCepat'] === null ? 'tanpa jam pulang' : $item['menitPulangCepat'].' menit' }}</span>
                                        @else
                                            <span class="text-gray-500">—</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <span class="font-semibold text-gray-900">{{ number_format($item['persenTotal'], 2, ',', '.') }}%</span>
                                        @if ($item['potonganNominal'] !== null)
                                            <span class="block text-xs text-gray-500">Rp {{ number_format($item['potonganNominal'], 0, ',', '.') }}</span>
                                        @endif
                                    </x-table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-table>
                @endif
            </x-card>

            <x-card title="Ringkasan potongan" subtitle="Termasuk koreksi manual yang dicatat admin.">
                <dl class="flex flex-col gap-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-600">Total sebelum koreksi</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($detail['ringkasan']['totalPersenAwal'], 2, ',', '.') }}%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-600">Koreksi manual</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($detail['ringkasan']['koreksiPersen'], 2, ',', '.') }}%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-2">
                        <dt class="font-semibold text-gray-900">Total potongan</dt>
                        <dd class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenAkhir'], 2, ',', '.') }}%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-600">Nominal dasar TUKIN</dt>
                        <dd class="font-medium text-gray-900">{{ $detail['ringkasan']['nominal'] === null ? 'Belum diatur' : 'Rp '.number_format($detail['ringkasan']['nominal'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-600">Potongan (Rp)</dt>
                        <dd class="font-semibold text-gray-900">{{ $detail['ringkasan']['potonganNominal'] === null ? '—' : 'Rp '.number_format($detail['ringkasan']['potonganNominal'], 0, ',', '.') }}</dd>
                    </div>
                </dl>

                @if ($detail['koreksi'] !== [])
                    <div class="mt-3 flex flex-col gap-1 border-t border-gray-100 pt-3 text-xs text-gray-600">
                        @foreach ($detail['koreksi'] as $koreksi)
                            <p>
                                Koreksi {{ number_format($koreksi['persen'], 2, ',', '.') }}%:
                                {{ $koreksi['alasan'] }}
                                <span class="text-gray-400">({{ $koreksi['pembuat'] ?? 'admin' }}, {{ $koreksi['dibuat_pada'] }})</span>
                            </p>
                        @endforeach
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.pegawai>
