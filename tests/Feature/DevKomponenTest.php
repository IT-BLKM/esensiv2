<?php

/*
 * CI tidak menjalankan `npm run build` dan `public/build` ada di .gitignore,
 * jadi manifest Vite tidak ada. withoutVite() men-stempel directive @vite
 * supaya halaman referensi tetap bisa diuji tanpa build asset.
 */
beforeEach(fn () => $this->withoutVite());

test('serves the component reference page outside production', function () {
    $this->get(route('dev.komponen'))
        ->assertOk()
        ->assertSee('Referensi Komponen')
        ->assertSee('Design system');
});

test('renders every core component on the reference page', function () {
    $halaman = $this->get(route('dev.komponen'))->assertOk()->getContent();

    expect($halaman)
        ->toContain('bg-brand-500')
        ->toContain('class="inline-flex items-center justify-center gap-2 rounded-md font-medium')
        ->toContain('min-w-full divide-y divide-gray-200');
});
