<x-layouts.admin title="Tambah Departemen">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Tambah departemen</h1>
            <p class="mt-1 text-sm text-gray-600">Kode departemen bersifat unik dan maksimal 4 karakter.</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.departemen.store') }}" class="flex flex-col gap-4">
                @csrf

                <div>
                    <label for="kode" class="block text-sm font-medium text-gray-700">Kode departemen</label>
                    <x-input
                        id="kode"
                        name="kode"
                        :value="old('kode')"
                        :invalid="$errors->has('kode')"
                        maxlength="4"
                        class="mt-1"
                    />
                    @error('kode')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama departemen</label>
                    <x-input
                        id="nama"
                        name="nama"
                        :value="old('nama')"
                        :invalid="$errors->has('nama')"
                        class="mt-1"
                    />
                    @error('nama')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan departemen</x-button>
                    <a
                        href="{{ route('admin.departemen.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
