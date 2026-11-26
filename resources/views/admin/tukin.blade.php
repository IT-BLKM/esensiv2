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

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
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
                <p class="text-xs text-gray-600">Total setelah koreksi</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($ringkasan['totalPersenAkhir'], 2, ',', '.') }}%</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Hari ada potongan</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">{{ $ringkasan['totalHariPotongan'] }}</p>
            </x-card>
            <x-card>
                <p class="text-xs text-gray-600">Potongan (Rp)</p>
                <p class="mt-1 text-xl font-semibold text-gray-900">
                    {{ $ringkasan['totalNominalPotongan'] === null ? '—' : 'Rp '.number_format($ringkasan['totalNominalPotongan'], 0, ',', '.') }}
                </p>
            </x-card>
        </div>

        @if ($bulan !== null)
            <x-card title="Kunci periode {{ $labelPeriode }}" subtitle="Setelah dikunci, presensi dan approval pada periode ini ditolak.">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($terkunci)
                        <x-badge variant="warning">Periode terkunci</x-badge>
                        @if ($periode?->dikunci_pada)
                            <p class="text-xs text-gray-600">
                                Dikunci {{ $periode->dikunci_pada->format('d-m-Y H:i') }}
                                oleh {{ $periode->dikunciOleh?->nama ?? 'admin' }}.
                            </p>
                        @endif
                        <form method="POST" action="{{ route('admin.tukin.periode.buka', $periode) }}" class="ml-auto">
                            @csrf
                            <input type="hidden" name="tahun" value="{{ $tahun }}">
                            <input type="hidden" name="bulan" value="{{ $bulan }}">
                            <x-button type="submit" variant="secondary" size="sm">Buka kunci</x-button>
                        </form>
                    @else
                        <p class="text-sm text-gray-600">Periode ini belum dikunci. Data presensi masih bisa berubah.</p>
                        <form method="POST" action="{{ route('admin.tukin.periode.kunci') }}" class="ml-auto flex flex-wrap items-end gap-2">
                            @csrf
                            <input type="hidden" name="tahun" value="{{ $tahun }}">
                            <input type="hidden" name="bulan" value="{{ $bulan }}">
                            <div>
                                <label for="catatan-kunci" class="block text-xs font-medium text-gray-600">Catatan (opsional)</label>
                                <x-input id="catatan-kunci" name="catatan" class="mt-1" placeholder="Mis. payroll November sudah final" />
                            </div>
                            <x-button type="submit" size="sm">Kunci periode</x-button>
                        </form>
                    @endif
                </div>
            </x-card>
        @endif

        @if ($detail !== null && $pegawai !== null)
            <x-card title="Rincian {{ $pegawai->nama }}" subtitle="Sumber potongan per tanggal pada {{ $labelPeriode }}.">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-gray-500">
                        Status terkunci tidak menghalangi koreksi; setiap koreksi tercatat di audit.
                    </p>
                    <a
                        href="{{ route('admin.tukin.slip', [
                            'pegawai' => $pegawai,
                            'bulan' => $bulan ?? '',
                            'tahun' => $tahun,
                        ]) }}"
                        class="rounded-md border border-brand-600 bg-white px-3 py-1.5 text-sm font-medium text-brand-700 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                    >Cetak slip PDF</a>
                </div>

                <div class="mb-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:grid-cols-7">
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Total % TL</p>
                        <p class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenTl'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Total % PSW</p>
                        <p class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenPsw'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Koreksi</p>
                        <p class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['koreksiPersen'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Hari diganti</p>
                        <p class="font-semibold text-gray-900">{{ $detail['ringkasan']['jumlahPenggantian'] }}</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Total potongan</p>
                        <p class="font-semibold text-gray-900">{{ number_format($detail['ringkasan']['totalPersenAkhir'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Nominal dasar</p>
                        <p class="font-semibold text-gray-900">{{ $detail['ringkasan']['nominal'] === null ? '—' : 'Rp '.number_format($detail['ringkasan']['nominal'], 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-md bg-gray-50 px-3 py-2">
                        <p class="text-xs text-gray-600">Potongan (Rp)</p>
                        <p class="font-semibold text-gray-900">{{ $detail['ringkasan']['potonganNominal'] === null ? '—' : 'Rp '.number_format($detail['ringkasan']['potonganNominal'], 0, ',', '.') }}</p>
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

                <div class="mt-4 border-t border-gray-100 pt-4">
                    <h3 class="text-sm font-semibold text-gray-900">Koreksi manual</h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Gunakan nilai positif untuk menambah potongan, negatif untuk mengurangi.
                        Koreksi tidak dihapus; untuk membatalkan, tambahkan nilai berlawanan.
                    </p>

                    @if ($detail['koreksi'] !== [])
                        <div class="mt-2 flex flex-col gap-1 rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-600">
                            @foreach ($detail['koreksi'] as $koreksi)
                                <p class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span>
                                        {{ number_format($koreksi['persen'], 2, ',', '.') }}% —
                                        {{ $koreksi['alasan'] }}
                                    </span>
                                    <span class="text-gray-400">
                                        {{ $koreksi['pembuat'] ?? 'admin' }} · {{ $koreksi['dibuat_pada'] }}
                                    </span>
                                </p>
                            @endforeach
                        </div>
                    @endif

                    @if ($bulan === null)
                        <p class="mt-2 text-xs text-gray-500">Pilih bulan tertentu untuk menambahkan koreksi.</p>
                    @else
                        <form method="POST" action="{{ route('admin.tukin.koreksi.store') }}" class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-[10rem_1.5fr_auto] sm:items-end">
                            @csrf
                            <input type="hidden" name="id_pegawai" value="{{ $pegawai->id }}">
                            <input type="hidden" name="tahun" value="{{ $tahun }}">
                            <input type="hidden" name="bulan" value="{{ $bulan }}">
                            <div>
                                <label for="persen-koreksi" class="block text-xs font-medium text-gray-600">Koreksi (%)</label>
                                <x-input id="persen-koreksi" name="persen" type="number" step="0.01" min="-100" max="100" :invalid="$errors->has('persen')" class="mt-1" />
                                @error('persen')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="alasan-koreksi" class="block text-xs font-medium text-gray-600">Alasan</label>
                                <x-input id="alasan-koreksi" name="alasan" :invalid="$errors->has('alasan')" class="mt-1" placeholder="Mis. koreksi data presensi" />
                                @error('alasan')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-button type="submit" size="sm">Tambah koreksi</x-button>
                        </form>
                    @endif
                </div>
            </x-card>
        @endif

        <x-card title="Rekap per departemen" subtitle="Akumulasi potongan pegawai pada periode {{ $labelPeriode }}.">
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Departemen</x-table.th>
                        <x-table.th align="center">Pegawai</x-table.th>
                        <x-table.th align="center">Kena potongan</x-table.th>
                        <x-table.th align="right">% TL</x-table.th>
                        <x-table.th align="right">% PSW</x-table.th>
                        <x-table.th align="right">Total akhir</x-table.th>
                        <x-table.th align="right">Potongan Rp</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($perDepartemen as $departemen)
                        <tr>
                            <x-table.td>{{ $departemen['departemen'] }}</x-table.td>
                            <x-table.td align="center">{{ $departemen['jumlahPegawai'] }}</x-table.td>
                            <x-table.td align="center">{{ $departemen['pegawaiKena'] }}</x-table.td>
                            <x-table.td align="right">{{ number_format($departemen['totalPersenTl'], 2, ',', '.') }}%</x-table.td>
                            <x-table.td align="right">{{ number_format($departemen['totalPersenPsw'], 2, ',', '.') }}%</x-table.td>
                            <x-table.td align="right"><strong>{{ number_format($departemen['totalPersenAkhir'], 2, ',', '.') }}%</strong></x-table.td>
                            <x-table.td align="right">
                                {{ $departemen['potonganNominal'] === null ? '—' : 'Rp '.number_format($departemen['potonganNominal'], 0, ',', '.') }}
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </x-card>

        <x-card title="Tren potongan {{ $tahun }}" subtitle="Total potongan seluruh pegawai per bulan.">
            <x-table>
                <thead>
                    <tr>
                        <x-table.th>Bulan</x-table.th>
                        <x-table.th align="center">Pegawai kena</x-table.th>
                        <x-table.th align="right">% TL</x-table.th>
                        <x-table.th align="right">% PSW</x-table.th>
                        <x-table.th align="right">Total akhir</x-table.th>
                        <x-table.th align="right">Potongan Rp</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($tren as $bulanTren)
                        <tr>
                            <x-table.td>{{ $bulanTren['label'] }}</x-table.td>
                            <x-table.td align="center">{{ $bulanTren['pegawaiKena'] }}</x-table.td>
                            <x-table.td align="right">{{ number_format($bulanTren['totalPersenTl'], 2, ',', '.') }}%</x-table.td>
                            <x-table.td align="right">{{ number_format($bulanTren['totalPersenPsw'], 2, ',', '.') }}%</x-table.td>
                            <x-table.td align="right"><strong>{{ number_format($bulanTren['totalPersenAkhir'], 2, ',', '.') }}%</strong></x-table.td>
                            <x-table.td align="right">
                                {{ $bulanTren['potonganNominal'] === null ? '—' : 'Rp '.number_format($bulanTren['potonganNominal'], 0, ',', '.') }}
                            </x-table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-table>
        </x-card>

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
                        <x-table.th align="right">Koreksi</x-table.th>
                        <x-table.th align="right">Total akhir</x-table.th>
                        <x-table.th align="right">Potongan Rp</x-table.th>
                        <x-table.th align="right">Aksi</x-table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($baris as $item)
                        @php($r = $item['ringkasan'])
                        <tr class="@if ($r['totalPersenAkhir'] > 0) bg-red-50/40 @endif">
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
                            <x-table.td align="right">{{ number_format($r['koreksiPersen'], 2, ',', '.') }}%</x-table.td>
                            <x-table.td align="right">
                                <span class="font-semibold text-gray-900">{{ number_format($r['totalPersenAkhir'], 2, ',', '.') }}%</span>
                            </x-table.td>
                            <x-table.td align="right">
                                {{ $r['potonganNominal'] === null ? '—' : 'Rp '.number_format($r['potonganNominal'], 0, ',', '.') }}
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
