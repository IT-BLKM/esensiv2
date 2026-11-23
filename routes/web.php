<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Satu guard `web`, satu halaman login. Route admin/pegawai menyusul di #19;
 * throttle login menyusul di #20 (docs/panduan-kolaborasi.md §13).
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

/*
 * Halaman referensi design system (docs/konvensi-ui.md §6).
 * Hanya untuk lingkungan lokal/testing — tidak boleh terlihat di produksi.
 */
if (! app()->isProduction()) {
    Route::get('/dev/komponen', fn () => view('dev.komponen'))->name('dev.komponen');
}
