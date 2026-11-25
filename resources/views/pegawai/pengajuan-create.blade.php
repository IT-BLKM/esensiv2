<x-layouts.pegawai title="Pengajuan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pengajuan</h1>
            <p class="mt-1 text-sm text-gray-600">Ajukan cuti, sakit, atau dinas luar beserta lampirannya.</p>
        </div>

        @if (! $adaPegawai)
            <x-alert variant="warning" title="Data pegawai belum tersedia">
                Akun ini belum tertaut ke data pegawai. Hubungi administrator sebelum mengirim pengajuan.
            </x-alert>
        @endif

        @if ($cuti->isEmpty())
            <x-alert variant="warning" title="Jenis cuti belum tersedia">
                Hubungi administrator untuk menambah master jenis cuti sebelum mengajukan cuti.
            </x-alert>
        @endif

        @if ($pendingTahunan !== null)
            <x-alert variant="info" title="Sudah ada pengajuan Cuti Tahunan">
                Pengajuan Cuti Tahunan Anda masih menunggu keputusan.
                <a
                    href="{{ route('pengajuan.edit', $pendingTahunan) }}"
                    class="font-medium text-brand-700 underline underline-offset-4 hover:text-brand-800"
                >Ubah pengajuan yang masih pending</a>
                daripada membuat pengajuan baru.
            </x-alert>
        @endif

        <x-card title="Form pengajuan" subtitle="Pilih jenis, rentang tanggal, dan lampiran yang diminta.">
            @include('pegawai.partials.pengajuan-form', [
                'aksi' => route('pengajuan.store'),
                'metode' => 'POST',
                'pengajuan' => null,
                'tombol' => 'Kirim pengajuan',
            ])
        </x-card>
    </div>

    @push('scripts')
        @vite('resources/js/pengajuan.js')
    @endpush
</x-layouts.pegawai>
