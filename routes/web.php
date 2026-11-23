<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => 'Dashboard pegawai')
        ->middleware('role:pegawai')
        ->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', fn () => 'Dashboard admin')->name('dashboard');
    });
});

/*
 * Halaman referensi design system (docs/konvensi-ui.md §6).
 * Hanya untuk lingkungan lokal/testing — tidak boleh terlihat di produksi.
 */
if (! app()->isProduction()) {
    Route::get('/dev/komponen', fn () => view('dev.komponen'))->name('dev.komponen');
}
