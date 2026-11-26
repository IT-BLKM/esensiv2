<x-layouts.admin title="Ubah Nominal TUKIN">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Ubah nominal TUKIN</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $nominal->jabatan }}</p>
        </div>

        <x-card>
            <form method="POST" action="{{ route('admin.tukin-nominal.update', $nominal) }}" class="flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                    <x-input id="jabatan" name="jabatan" :value="old('jabatan', $nominal->jabatan)" :invalid="$errors->has('jabatan')" class="mt-1" />
                    @error('jabatan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nominal" class="block text-sm font-medium text-gray-700">Nominal dasar (Rp)</label>
                    <x-input id="nominal" name="nominal" type="number" min="0" :value="old('nominal', $nominal->nominal)" :invalid="$errors->has('nominal')" class="mt-1" />
                    @error('nominal')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="keterangan" class="block text-sm font-medium text-gray-700">Keterangan</label>
                    <x-input id="keterangan" name="keterangan" :value="old('keterangan', $nominal->keterangan)" :invalid="$errors->has('keterangan')" class="mt-1" />
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Simpan perubahan</x-button>
                    <a href="{{ route('admin.tukin-nominal.index') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">Batal</a>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
