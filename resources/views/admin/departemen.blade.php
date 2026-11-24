<x-layouts.admin title="Departemen">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Departemen</h1>
            <p class="mt-1 text-sm text-gray-600">Daftar departemen yang membagi struktur pegawai.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #25</strong> (CRUD Departemen) — pola pertama
                yang akan ditiru halaman CRUD lain. Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/admin/departemen.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
