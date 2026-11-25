<x-layouts.admin title="Tambah Jam Kerja">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Tambah jam kerja</h1>
            <p class="mt-1 text-sm text-gray-600">
                Kode unik 4 karakter. Urutan jam: awal masuk ≤ jam masuk ≤ akhir masuk ≤ jam pulang.
            </p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.jam-kerja.store') }}" class="flex flex-col gap-4">
                @csrf

                <div>
                    <label for="kode" class="block text-sm font-medium text-gray-700">Kode jam kerja</label>
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
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama jam kerja</label>
                    <x-input
                        id="nama"
                        name="nama"
                        :value="old('nama')"
                        :invalid="$errors->has('nama')"
                        maxlength="25"
                        class="mt-1"
                    />
                    @error('nama')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="awal_masuk" class="block text-sm font-medium text-gray-700">Awal masuk</label>
                        <x-input
                            id="awal_masuk"
                            type="time"
                            name="awal_masuk"
                            :value="old('awal_masuk')"
                            :invalid="$errors->has('awal_masuk')"
                            class="mt-1"
                        />
                        @error('awal_masuk')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="jam_masuk" class="block text-sm font-medium text-gray-700">Jam masuk</label>
                        <x-input
                            id="jam_masuk"
                            type="time"
                            name="jam_masuk"
                            :value="old('jam_masuk')"
                            :invalid="$errors->has('jam_masuk')"
                            class="mt-1"
                        />
                        @error('jam_masuk')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="akhir_masuk" class="block text-sm font-medium text-gray-700">Akhir masuk</label>
                        <x-input
                            id="akhir_masuk"
                            type="time"
                            name="akhir_masuk"
                            :value="old('akhir_masuk')"
                            :invalid="$errors->has('akhir_masuk')"
                            class="mt-1"
                        />
                        @error('akhir_masuk')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="jam_pulang" class="block text-sm font-medium text-gray-700">Jam pulang</label>
                        <x-input
                            id="jam_pulang"
                            type="time"
                            name="jam_pulang"
                            :value="old('jam_pulang')"
                            :invalid="$errors->has('jam_pulang')"
                            class="mt-1"
                        />
                        @error('jam_pulang')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan jam kerja</x-button>
                    <a
                        href="{{ route('admin.jam-kerja.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
