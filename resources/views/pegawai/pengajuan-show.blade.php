<x-layouts.pegawai title="Detail pengajuan">
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
            'dl' => $pengajuan->mode_dl === 'setengah' ? 'Dinas Luar (Setengah)' : 'Dinas Luar (Penuh)',
            default => ucfirst($pengajuan->jenis),
        };

        $mulai = \Illuminate\Support\Carbon::parse((string) $pengajuan->tanggal_mulai)->locale('id');
        $akhir = \Illuminate\Support\Carbon::parse((string) $pengajuan->tanggal_berakhir)->locale('id');
    @endphp

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Detail pengajuan</h1>
                <p class="mt-1 text-sm text-gray-600">Rincian pengajuan dan keputusan admin.</p>
            </div>

            <a
                href="{{ route('pengajuan.index') }}"
                class="text-sm font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
            >
                Kembali ke daftar
            </a>
        </div>

        <x-card title="Informasi pengajuan">
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :variant="$variantStatus">{{ $labelStatus }}</x-badge>
                    <span class="text-sm text-gray-600">{{ $labelJenis }}</span>
                </div>

                <dl class="flex flex-col divide-y divide-gray-100 text-sm">
                    <div class="flex items-start justify-between gap-4 py-3 first:pt-0">
                        <dt class="text-gray-600">Tanggal</dt>
                        <dd class="text-right font-medium text-gray-900">
                            {{ $mulai->translatedFormat('d M Y') }}
                            @if ($pengajuan->tanggal_mulai !== $pengajuan->tanggal_berakhir)
                                &ndash; {{ $akhir->translatedFormat('d M Y') }}
                            @endif
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
                                    href="{{ route('pengajuan.lampiran', $pengajuan) }}"
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
                        <dd class="text-right font-medium text-gray-900">
                            {{ $pengajuan->catatan ?? '—' }}
                        </dd>
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
            </div>
        </x-card>

        @if ($pengajuan->status === 'pending')
            <x-card title="Batalkan pengajuan" subtitle="Pengajuan yang masih menunggu dapat dibatalkan.">
                <div class="flex flex-col gap-3">
                    <x-alert variant="warning">
                        Pengajuan akan dihapus dan tidak bisa dipulihkan. Jika hanya ingin mengubah data,
                        gunakan tombol Ubah.
                    </x-alert>

                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('pengajuan.edit', $pengajuan) }}"
                            class="rounded-md border border-brand-600 bg-white px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"
                        >Ubah pengajuan</a>

                        <form
                            method="POST"
                            action="{{ route('pengajuan.cancel', $pengajuan) }}"
                            onsubmit="return confirm(@js('Batalkan pengajuan ini? Tindakan tidak bisa dibatalkan.'));"
                        >
                            @csrf
                            <x-button type="submit" variant="danger">Batalkan pengajuan</x-button>
                        </form>
                    </div>
                </div>
            </x-card>
        @endif
    </div>
</x-layouts.pegawai>
