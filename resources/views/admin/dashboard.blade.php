<x-layouts.admin title="Dashboard">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard admin</h1>
            <p class="mt-1 text-sm text-gray-600">Ringkasan kehadiran dan pengajuan yang menunggu tindakan.</p>
        </div>

        <x-card>
            <x-alert variant="info" title="Belum ada rekap">
                Angka rekap diisi bersama fitur pendukungnya:
                <strong>monitoring kehadiran hari ini</strong> (issue #41) dan
                <strong>persetujuan pengajuan</strong> (issue #39).
                Sementara itu, gunakan menu di sebelah kiri untuk membuka tiap halaman.
            </x-alert>
        </x-card>
    </div>
</x-layouts.admin>
