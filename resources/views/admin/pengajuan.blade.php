<x-layouts.admin title="Pengajuan">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Pengajuan</h1>
            <p class="mt-1 text-sm text-gray-600">Pengajuan cuti, sakit, dan dinas luar yang menunggu persetujuan.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #39</strong> (panel admin: approve/reject
                pengajuan — transaksi + saldo + presensi). Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/admin/pengajuan.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
