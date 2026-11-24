<x-layouts.admin title="Cuti">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Cuti</h1>
            <p class="mt-1 text-sm text-gray-600">Jenis cuti beserta hari yang berlaku untuk tiap jenis.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Halaman kosong">
                Isi halaman ini dibangun oleh <strong>issue #27</strong> (CRUD Cuti — jenis dan aturan saja,
                tanpa saldo). Saat fitur itu selesai, ganti isi
                <code class="rounded bg-gray-100 px-1">resources/views/admin/cuti.blade.php</code>.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
