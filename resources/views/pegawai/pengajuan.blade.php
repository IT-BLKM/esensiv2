<x-layouts.pegawai title="Pengajuan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pengajuan</h1>
            <p class="mt-1 text-sm text-gray-600">Ajukan cuti, sakit, atau dinas luar beserta lampirannya.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #36</strong> (form pengajuan cuti/sakit/dinas luar
                + lampiran + validasi) dan <strong>issue #38</strong> (daftar pengajuan milik pegawai).
                Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/pegawai/pengajuan.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.pegawai>
