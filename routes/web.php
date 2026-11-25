<?php

use App\Http\Controllers\Admin\CutiController;
use App\Http\Controllers\Admin\DepartemenController;
use App\Http\Controllers\Admin\JamKerjaController;
use App\Http\Controllers\Admin\KonfigurasiJamKerjaController;
use App\Http\Controllers\Admin\PegawaiController;
use App\Http\Controllers\Admin\PegawaiPasswordController;
use App\Http\Controllers\Admin\PengajuanController as PengajuanAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\Pegawai\DashboardController;
use App\Http\Controllers\Pegawai\HistoriPresensiController;
use App\Http\Controllers\Pegawai\LaporanController;
use App\Http\Controllers\Pegawai\PengajuanController;
use App\Http\Controllers\Pegawai\PengajuanDaftarController;
use App\Http\Controllers\Pegawai\PengajuanDetailController;
use App\Http\Controllers\Pegawai\PengajuanFormController;
use App\Http\Controllers\Pegawai\PresensiController;
use App\Http\Controllers\Pegawai\PresensiHalamanController;
use App\Http\Controllers\Pegawai\StorePresensiController;
use App\Http\Controllers\PengajuanLampiranController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('role:pegawai')
        ->name('dashboard');

    /*
     * Empat halaman isi bottom navigation pegawai (issue #24). Nama route-nya
     * sudah bentuk akhir mengikuti konvensi (panduan-kolaborasi.md §13):
     * tiap issue fitur (#31 #33 #36 #38) mengganti closure ini dengan
     * controller sendiri, nama route tidak berubah sehingga tautan
     * bottom nav tidak pernah rusak.
     */
    Route::middleware('role:pegawai')->group(function () {
        Route::get('/presensi', PresensiHalamanController::class)->name('presensi.index');

        /*
         * Penyimpanan presensi (issue #32): validasi radius, foto privat, dan
         * alur masuk -> pulang di StorePresensiAction. Respons selalu JSON
         * terstruktur agar bisa dibaca halaman presensi (#31).
         */
        Route::post('/presensi', StorePresensiController::class)->name('presensi.store');

        Route::get('/histori', HistoriPresensiController::class)->name('histori.index');

        /*
         * Daftar + form pengajuan pegawai (issue #36–#38). Nama route
         * `pengajuan.index` tetap dipakai bottom navigation; form punya route
         * sendiri `pengajuan.create`.
         */
        Route::get('/pengajuan', PengajuanDaftarController::class)->name('pengajuan.index');
        Route::get('/pengajuan/create', [PengajuanFormController::class, 'create'])->name('pengajuan.create');
        Route::post('/pengajuan', [PengajuanFormController::class, 'store'])->name('pengajuan.store');
        Route::get('/pengajuan/{pengajuan}/edit', [PengajuanFormController::class, 'edit'])->name('pengajuan.edit');
        Route::put('/pengajuan/{pengajuan}', [PengajuanFormController::class, 'update'])->name('pengajuan.update');
        Route::get('/pengajuan/{pengajuan}/lampiran', PengajuanLampiranController::class)
            ->name('pengajuan.lampiran');
        Route::get('/pengajuan/{pengajuan}', PengajuanDetailController::class)
            ->name('pengajuan.show');

        Route::get('/profil', fn () => view('pegawai.profil'))->name('profil');
    });

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::post('/password', [PasswordController::class, 'update'])->name('password.update');

    /*
     * Akses data pengajuan/presensi/laporan. Tidak pakai middleware role:
     * admin boleh semua, pegawai dibatasi Policy ke data miliknya (anti-IDOR).
     * Route memakai binding model, bukan `{nip}`/`{kode}` seperti app lama.
     */
    Route::prefix('pegawai')->name('pegawai.')->group(function () {
        Route::get('/pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
        Route::get('/pengajuan/{pengajuan}/edit', [PengajuanController::class, 'edit'])->name('pengajuan.edit');
        Route::get('/pengajuan/{pengajuan}', [PengajuanController::class, 'show'])->name('pengajuan.show');
        Route::put('/pengajuan/{pengajuan}', [PengajuanController::class, 'update'])->name('pengajuan.update');
        Route::delete('/pengajuan/{pengajuan}', [PengajuanController::class, 'destroy'])->name('pengajuan.destroy');

        Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
        Route::get('/presensi/{presensi}', [PresensiController::class, 'show'])->name('presensi.show');

        Route::get('/laporan/{pegawai}', [LaporanController::class, 'show'])->name('laporan.show');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', fn () => view('admin.dashboard'))->name('dashboard');

        Route::get('/pegawai/{pegawai}/password/edit', [PegawaiPasswordController::class, 'edit'])
            ->name('pegawai.password.edit');
        Route::post('/pegawai/{pegawai}/password', [PegawaiPasswordController::class, 'update'])
            ->name('pegawai.password.update');

        /*
         * Route penutup menu sidebar (issue #23). Nama route-nya sudah
         * bentuk akhir mengikuti konvensi (panduan-kolaborasi.md §13): tiap issue
         * fitur mengganti closure ini dengan Route::resource + controller sendiri,
         * nama route `admin.<menu>.index` tidak berubah sehingga tautan sidebar
         * tidak pernah rusak. Departemen sudah diganti di issue #25, jam kerja di #26,
         * konfigurasi jam kerja per pegawai di #29, jenis & aturan cuti di #27,
         * pegawai di #28.
         */
        Route::resource('pegawai', PegawaiController::class)
            ->parameters(['pegawai' => 'pegawai'])
            ->except('show');
        /*
         * Nama parameter dipaksa supaya cocok dengan argumen controller dan policy:
         * `Str::singular('departemen')` menghasilkan `departeman`, dan resource
         * `jam-kerja` menghasilkan wildcard `jam_kerja` bila dibiarkan.
         */
        Route::resource('departemen', DepartemenController::class)
            ->parameters(['departemen' => 'departemen'])
            ->except('show');
        Route::resource('jam-kerja', JamKerjaController::class)
            ->parameters(['jam-kerja' => 'jamKerja'])
            ->except('show');
        Route::resource('cuti', CutiController::class)
            ->parameters(['cuti' => 'cuti'])
            ->except('show');
        Route::get('/monitoring', fn () => view('admin.monitoring'))->name('monitoring.index');

        /*
         * Panel pengajuan (issue #39): daftar/filter, detail, approve/reject,
         * dan unduh lampiran privat. Nama index dipertahankan untuk sidebar.
         */
        Route::get('/pengajuan', [PengajuanAdminController::class, 'index'])->name('pengajuan.index');
        Route::get('/pengajuan/{pengajuan}', [PengajuanAdminController::class, 'show'])
            ->name('pengajuan.show');
        Route::get('/pengajuan/{pengajuan}/lampiran', PengajuanLampiranController::class)
            ->name('pengajuan.lampiran');
        Route::post('/pengajuan/{pengajuan}/approve', [PengajuanAdminController::class, 'approve'])
            ->name('pengajuan.approve');
        Route::post('/pengajuan/{pengajuan}/reject', [PengajuanAdminController::class, 'reject'])
            ->name('pengajuan.reject');
        Route::post('/pengajuan/{pengajuan}/cancel', [PengajuanAdminController::class, 'cancel'])
            ->name('pengajuan.cancel');

        Route::get('/laporan', fn () => view('admin.laporan'))->name('laporan.index');
        Route::get('/konfigurasi', [KonfigurasiJamKerjaController::class, 'index'])->name('konfigurasi.index');
        Route::get('/konfigurasi/{pegawai}/edit', [KonfigurasiJamKerjaController::class, 'edit'])
            ->name('konfigurasi.edit');
        Route::put('/konfigurasi/{pegawai}', [KonfigurasiJamKerjaController::class, 'update'])
            ->name('konfigurasi.update');
    });
});

/*
 * Satu guard `web`, satu halaman login. Throttle 5 percobaan/menit mengunci
 * route login sesuai docs/panduan-kolaborasi.md §13 aturan 7.
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
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
