<x-layouts.admin title="Tambah Pegawai">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Tambah pegawai</h1>
            <p class="mt-1 text-sm text-gray-600">Akun user dibuat otomatis dengan password awal acak yang ditampilkan sekali setelah disimpan.</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.pegawai.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nip" class="block text-sm font-medium text-gray-700">NIP</label>
                        <x-input
                            id="nip"
                            name="nip"
                            :value="old('nip')"
                            :invalid="$errors->has('nip')"
                            maxlength="18"
                            inputmode="numeric"
                            class="mt-1"
                        />
                        @error('nip')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="no_hp" class="block text-sm font-medium text-gray-700">No. HP</label>
                        <x-input
                            id="no_hp"
                            name="no_hp"
                            :value="old('no_hp')"
                            :invalid="$errors->has('no_hp')"
                            maxlength="15"
                            inputmode="numeric"
                            class="mt-1"
                        />
                        @error('no_hp')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700">Nama</label>
                        <x-input
                            id="nama"
                            name="nama"
                            :value="old('nama')"
                            :invalid="$errors->has('nama')"
                            maxlength="255"
                            class="mt-1"
                        />
                        @error('nama')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                        <x-input
                            id="jabatan"
                            name="jabatan"
                            :value="old('jabatan')"
                            :invalid="$errors->has('jabatan')"
                            maxlength="255"
                            class="mt-1"
                        />
                        @error('jabatan')
                            <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="id_dept" class="block text-sm font-medium text-gray-700">Departemen</label>
                    <select
                        id="id_dept"
                        name="id_dept"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-xs focus:border-brand-600 focus:ring-2 focus:ring-brand-600/30 focus:outline-none"
                    >
                        <option value="">Pilih departemen</option>
                        @foreach ($departemen as $dept)
                            <option value="{{ $dept->id }}" @selected(old('id_dept') == $dept->id)>{{ $dept->kode }} — {{ $dept->nama }}</option>
                        @endforeach
                    </select>
                    @error('id_dept')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="foto" class="block text-sm font-medium text-gray-700">Foto profil</label>
                    <x-input
                        id="foto"
                        name="foto"
                        type="file"
                        accept=".jpg,.jpeg,.png"
                        :invalid="$errors->has('foto')"
                        class="mt-1"
                    />
                    <p class="mt-1 text-xs text-gray-600">Opsional. Format JPG/JPEG/PNG, maksimal 2 MB.</p>
                    @error('foto')
                        <p class="mt-1 text-sm text-gray-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan pegawai</x-button>
                    <a
                        href="{{ route('admin.pegawai.index') }}"
                        class="rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
