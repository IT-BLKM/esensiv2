<x-layouts.admin title="Tinjau pengajuan">
    @php
        $labelStatus = match ($pengajuan->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => ucfirst($pengajuan->status),
        };

        $variantStatus = match ($pengajuan->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };

        $labelJenis = match ($pengajuan->jenis) {
            'cuti' => $pengajuan->cuti?->nama ?? 'Cuti',
            'sakit' => 'Sakit',
            'dl' => 'Dinas Luar',
            default => ucfirst($pengajuan->jenis),
        };

        $mulai = \Illuminate\Support\Carbon::parse((string) $pengajuan->tanggal_mulai)->locale('id');
        $akhir = \Illuminate\Support\Carbon::parse((string) $pengajuan->tanggal_berakhir)->locale('id');
    @endphp

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Tinjau pengajuan</h1>
                <p class="mt-1 text-sm text-gray-600">Periksa data sebelum menyetujui atau menolak.</p>
            </div>

            <a
                href="{{ route('admin.pengajuan.index') }}"
                class="text-sm font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
            >
                Kembali ke daftar
            </a>
        </div>

        @error('pengajuan')
            <x-alert variant="danger" title="Tidak bisa diproses">{{ $message }}</x-alert>
        @enderror

        <x-card title="Data pengajuan">
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :variant="$variantStatus">{{ $labelStatus }}</x-badge>
                    <span class="text-sm text-gray-600">{{ $labelJenis }}</span>
                </div>

                <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                    <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                        <dt class="text-gray-600">Pegawai</dt>
                        <dd class="text-right font-medium text-gray-900">
                            {{ $pengajuan->pegawai?->nama ?? '—' }}
                            <span class="block text-xs font-normal text-gray-600">{{ $pengajuan->pegawai?->nip }}</span>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-gray-600">Tanggal</dt>
                        <dd class="text-right font-medium text-gray-900">
                            {{ $mulai->translatedFormat('d M Y') }}
                            @if ($pengajuan->tanggal_mulai !== $pengajuan->tanggal_berakhir)
                                &ndash; {{ $akhir->translatedFormat('d M Y') }}
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-gray-600">Hari kerja</dt>
                        <dd class="text-right font-medium text-gray-900">
                            {{ count($hariKerja) }} hari
                            <span class="block text-xs font-normal text-gray-600">
                                Sabtu/Minggu dan hari tanpa shift tidak dibuatkan presensi.
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-gray-600">Keterangan</dt>
                        <dd class="text-right font-medium text-gray-900">{{ $pengajuan->keterangan }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-gray-600">Lampiran</dt>
                        <dd class="text-right font-medium text-gray-900">
                            @if ($pengajuan->lampiran === null)
                                <span class="font-normal text-gray-500">—</span>
                            @elseif ($adaLampiran)
                                <a
                                    href="{{ route('admin.pengajuan.lampiran', $pengajuan) }}"
                                    class="text-brand-700 underline underline-offset-4 hover:text-brand-800"
                                >
                                    Unduh lampiran
                                </a>
                            @else
                                <span class="font-normal text-gray-600">File lampiran tidak ditemukan di server.</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="text-gray-600">Catatan keputusan</dt>
                        <dd class="text-right font-medium text-gray-900">{{ $pengajuan->catatan ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-3 last:pb-0">
                        <dt class="text-gray-600">Diputuskan</dt>
                        <dd class="text-right font-medium text-gray-900">
                            @if ($pengajuan->disetujui_pada === null)
                                <span class="font-normal text-gray-500">Belum diputuskan</span>
                            @else
                                {{ \Illuminate\Support\Carbon::parse((string) $pengajuan->disetujui_pada)->locale('id')->translatedFormat('d M Y H:i') }}
                                @if ($pengajuan->penyetuju !== null)
                                    <span class="block text-xs font-normal text-gray-600">
                                        oleh {{ $pengajuan->penyetuju->nama }}
                                    </span>
                                @endif
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($hariKerja !== [])
                    <details class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        <summary class="cursor-pointer font-medium text-gray-800">
                            Lihat {{ count($hariKerja) }} tanggal hari kerja
                        </summary>
                        <ul class="mt-2 grid gap-1 text-xs text-gray-700 sm:grid-cols-2">
                            @foreach ($hariKerja as $hari)
                                <li>
                                    {{ \Illuminate\Support\Carbon::parse($hari['tanggal'])->locale('id')->translatedFormat('d M Y (l)') }}
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        </x-card>

        @if ($pengajuan->status === 'pending')
            <div class="grid gap-4 lg:grid-cols-2">
                <x-card title="Setujui" subtitle="Baris presensi dibuat untuk hari kerja di atas.">
                    <form method="POST" action="{{ route('admin.pengajuan.approve', $pengajuan) }}" class="flex flex-col gap-3">
                        @csrf

                        <div>
                            <label for="catatan_setujui" class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                            <textarea
                                id="catatan_setujui"
                                name="catatan_setujui"
                                rows="3"
                                maxlength="255"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                            >{{ old('catatan_setujui') }}</textarea>
                            @error('catatan_setujui')
                                <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-button type="submit" variant="primary">Setujui pengajuan</x-button>
                        </div>
                    </form>
                </x-card>

                <x-card title="Tolak" subtitle="Catatan alasan wajib diisi.">
                    <form method="POST" action="{{ route('admin.pengajuan.reject', $pengajuan) }}" class="flex flex-col gap-3">
                        @csrf

                        <div>
                            <label for="catatan_tolak" class="block text-sm font-medium text-gray-700">Alasan penolakan</label>
                            <textarea
                                id="catatan_tolak"
                                name="catatan_tolak"
                                rows="3"
                                maxlength="255"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                            >{{ old('catatan_tolak') }}</textarea>
                            @error('catatan_tolak')
                                <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-button type="submit" variant="danger">Tolak pengajuan</x-button>
                        </div>
                    </form>
                </x-card>
            </div>
        @endif
    </div>
</x-layouts.admin>
