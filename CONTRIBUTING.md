# Kontribusi — esensiv2

Aturan kerja tim (branch, commit, pull request, aturan main) ada di
[`docs/panduan-kolaborasi.md`](docs/panduan-kolaborasi.md), konvensi tampilan ada di
[`docs/konvensi-ui.md`](docs/konvensi-ui.md). File ini hanya mencatat **pola kode yang dipakai ulang**.

## Pola CRUD (ditetapkan di issue #25 — Departemen)

Departemen adalah contoh pertama. CRUD berikutnya menyalin bentuknya, bukan mengarang gaya baru.

| Lapisan | Lokasi | Aturan |
|---|---|---|
| Route | `routes/web.php`, grup `admin` | `Route::resource(...)->except('show')`; mutasi tidak pernah lewat `GET`. Paksa nama parameter bila `Str::singular` mengubah kata Indonesia (`departemen` → `departeman`) supaya cocok dengan argumen controller |
| Otorisasi | `app/Policies/DepartemenPolicy.php` | policy dipanggil lewat `$this->authorize(...)` di tiap aksi controller; middleware `role:admin` tetap jadi lapisan pertama |
| Validasi | `app/Http/Requests/Store…Request.php` dan `Update…Request.php` | tidak ada `$request->validate()` di controller; aturan `unique` saat update wajib `->ignore($model->id)` |
| Logika | `app/Actions/Departemen/*.php` | satu Action per operasi (`Cari`, `Create`, `Update`, `Delete`) yang disuntikkan ke controller; controller hanya meneruskan data |
| Tampilan | `resources/views/admin/departemen/{index,create,edit}.blade.php` | `x-layouts.admin`, `x-card`, `x-table`, `x-input`, `x-button`, dan `route()`; tanpa hex literal dan tanpa `dark:` |
| Pesan | flash `success` / `error` | layout admin menampilkan `success`, `warning`, `error`; `error` dipakai untuk aksi yang ditolak |

Alur controller tipis:

```text
FormRequest → Action → redirect()->route('admin.<menu>.index')->with('success'|'error', ...)
```

Halaman `index` menerima kata kunci `?q=` lalu menyerahkannya ke Action `Cari…`; Action itulah yang
menentukan filter dan urutan, bukan view atau controller.

**Jaga data.** FK `pegawai.id_dept` memakai `cascadeOnDelete`, jadi `DeleteDepartemenAction`
menolak menghapus departemen yang masih dipakai pegawai dan controller mengirim flash `error`.

**Test minimal tiap CRUD:** create sukses (assert baris tersimpan + flash), validasi error (assert
session errors), akses guest/pegawai ditolak, dan satu kasus gagal khusus (misal hapus data yang
masih dipakai). Contoh lengkap: `tests/Feature/Admin/DepartemenCrudTest.php`.
