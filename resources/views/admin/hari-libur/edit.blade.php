<x-layouts.admin title="Ubah Hari Libur">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Ubah hari libur</h1>
            <p class="mt-1 text-sm text-gray-600">Perbarui tanggal atau nama {{ $hariLibur->nama }}.</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.hari-libur.update', $hariLibur) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                    <x-input
                        id="tanggal"
                        name="tanggal"
                        type="date"
                        :value="old('tanggal', \Carbon\Carbon::parse((string) $hariLibur->tanggal)->format('Y-m-d'))"
                        :invalid="$errors->has('tanggal')"
                        class="mt-1"
                    />
                    @error('tanggal')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama hari libur</label>
                    <x-input
                        id="nama"
                        name="nama"
                        :value="old('nama', $hariLibur->nama)"
                        :invalid="$errors->has('nama')"
                        class="mt-1"
                    />
                    @error('nama')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan perubahan</x-button>
                    <a
                        href="{{ route('admin.hari-libur.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
