<x-layouts.admin title="Buat Pengajuan">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Buat pengajuan pegawai</h1>
                <p class="mt-1 text-sm text-gray-600">Catat pengajuan atas nama pegawai. Status awal tetap menunggu keputusan.</p>
            </div>

            <a
                href="{{ route('admin.pengajuan.index') }}"
                class="text-sm font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
            >
                Kembali ke daftar
            </a>
        </div>

        @if (! $adaPegawai)
            <x-alert variant="info" title="Pilih pegawai">
                Pilih pegawai untuk melihat sisa kuota cuti. Validasi kuota dan bentrok tanggal tetap dijalankan saat disimpan.
            </x-alert>
        @endif

        <x-card title="Form pengajuan" subtitle="Pilih pegawai, jenis, rentang tanggal, dan lampiran yang diminta.">
            @include('pegawai.partials.pengajuan-form', [
                'aksi' => route('admin.pengajuan.store'),
                'metode' => 'POST',
                'pengajuan' => null,
                'tombol' => 'Simpan pengajuan',
                'batal' => route('admin.pengajuan.index'),
                'daftarPegawai' => $daftarPegawai,
                'pegawaiTerpilih' => $idPegawai,
                'bisaLangsungSetujui' => true,
            ])
        </x-card>
    </div>

    @push('scripts')
        @vite('resources/js/pengajuan.js')
    @endpush
</x-layouts.admin>
