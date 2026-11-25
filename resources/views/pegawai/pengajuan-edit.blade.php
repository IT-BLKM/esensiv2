<x-layouts.pegawai title="Ubah pengajuan">
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Ubah pengajuan</h1>
                <p class="mt-1 text-sm text-gray-600">Perbarui pengajuan yang masih menunggu keputusan.</p>
            </div>

            <a
                href="{{ route('pengajuan.show', $pengajuan) }}"
                class="text-sm font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
            >
                Kembali ke detail
            </a>
        </div>

        @if (! $adaPegawai)
            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai. Hubungi administrator sebelum mengubah pengajuan.
            </x-alert>
        @endif

        <x-card title="Form pengajuan" subtitle="Ubah data yang perlu diperbaiki, lalu simpan.">
            @include('pegawai.partials.pengajuan-form', [
                'aksi' => route('pengajuan.update', $pengajuan),
                'metode' => 'PUT',
                'tombol' => 'Simpan perubahan',
                'batal' => route('pengajuan.show', $pengajuan),
            ])
        </x-card>
    </div>

    @push('scripts')
        @vite('resources/js/pengajuan.js')
    @endpush
</x-layouts.pegawai>
