# Konvensi UI — esensiv2

> Pegangan desain tampilan untuk seluruh anggota tim. Baca sebelum menulis halaman pertama.
> Halaman hidup: **`/dev/komponen`** (hanya lingkungan lokal).
> Terkait: `docs/panduan-kolaborasi.md` §13 (konvensi route).

---

## 1. Kenapa dokumen ini ada

Tiga hal yang membuat tampilan sebuah tim cepat tidak konsisten:

1. Setiap orang memilih warna/utility sendiri.
2. Komponen yang sama ditulis ulang dengan bentuk berbeda.
3. Tidak ada yang tahu mana yang "benar" karena tidak ada aturan tertulis.

Dokumen + halaman referensi + test adalah jawabannya. **Konsistensi datang dari mekanisme,
bukan dari niat.**

---

## 2. Stack (keputusan QA, 23 Nov 2026)

| | Keputusan |
|---|---|
| CSS | **Tailwind CSS v4** — sudah terpasang, konfigurasi di CSS (`@theme`), tanpa `tailwind.config.js` |
| Komponen | **Blade component buatan tim** di `resources/views/components/` |
| Halaman referensi | `/dev/komponen` |

**Yang ditolak, beserta alasannya:**

| Ditolak | Alasan |
|---|---|
| `shadcn/ui` | Dibuat untuk React (ada port komunitas untuk Vue/Svelte). **Tidak ada versi Blade** — memaksanya berarti membawa framework JS kedua. |
| Tabler / Bootstrap (issue #23 lama) | Dua CSS framework utility berdampingan dengan Tailwind → justru sumber inkonsistensi. |
| DaisyUI | Layak, tapi mengikat gaya DaisyUI sendiri. Bila suatu saat dipakai, tetap wajib lewat token di bawah. |

> **Ide shadcn tetap dipakai:** komponen di-*copy* ke repo dan dimiliki tim, bukan dependency hitam.
> Versi Blade-nya adalah `resources/views/components/*.blade.php`.

---

## 3. Warna

### 3.1 Sumber

Palet berasal dari logo Kemenkes. **Semua** didefinisikan di `resources/css/app.css` di dalam
blok `@theme`:

```
brand  #00B9AD   warna utama, aksi primer, navigasi aktif
accent #CDDC29   sorotan, penanda penting
info   #60C0D0   informasi sekunder, badge status
netral ——        memakai palet `gray-*` bawaan Tailwind
```

### 3.2 Kenapa ada skala 50–950

Keempat warna logo **tidak lulus WCAG sebagai teks di atas putih**:

| Warna | vs putih | Kelayakan |
|---|---|---|
| `#00B9AD` | 2.46 | ❌ teks |
| `#CDDC29` | 1.51 | ❌ teks |
| `#60C0D0` | 2.11 | ❌ teks |
| `#8E8E8E` | 3.28 | ❌ teks (baru lulus untuk border) |

Pasangan antar-warna juga buruk — teal vs sky hanya **1.16**, praktis tak terbedakan.
Makanya dibuat **turunan gelap** yang lolos ambang, dan aturan pemakaiannya:

| Step | Kontras vs putih | Boleh dipakai untuk |
|---|---|---|
| **700–950** | ≥ **4.5:1** (WCAG 1.4.3) | **Teks**, tautan, judul, label |
| **600** | ≥ **3:1** (WCAG 1.4.11) | Border, garis pemisah, elemen UI |
| **500** | warna **persis** logo | **Fill/latar saja** — tombol aktif, sidebar, banner |
| 50–400 | < 3:1 | Latar tint, hover ringan, badge |

**Teks di atas warna `500`** harus gelap (`text-gray-900`), **tidak pernah putih** —
putih di atas `#00B9AD` hanya 2.46:1.

### 3.3 Aturan keras

1. **Tidak ada hex literal di dalam file `.blade.php`.** Semua lewat token.
2. **Tidak ada warna baru di halaman fitur.** Butuh warna baru? Tambahkan token dulu di `app.css`
   lewat PR, lengkapi dengan step 600–950 yang lolos kontras.
3. **Status jangan dibedakan dengan warna saja** — selalu sertakan teks/label
   (mis. `<x-badge variant="warning">Terlambat</x-badge>`, bukan hanya titik kuning).

Aturan ini **dijaga test**, bukan cuma niat: `tests/Feature/DesignTokensTest.php` membaca
`app.css` dan memaksa langkah ≥700 ≥4.5:1, langkah 600 ≥3:1, dan `-500` persis warna logo.
**Mengubah token sembarangan = test merah.**

---

## 4. Komponen

Semua di `resources/views/components/`. Pakai komponen, jangan menyusun utility dari nol.

| Komponen | Contoh | Catatan |
|---|---|---|
| `x-button` | `<x-button variant="primary" size="sm">Simpan</x-button>` | `primary` · `secondary` · `ghost` · `danger`; `sm` · `md` · `lg` |
| `x-card` | `<x-card title="Pegawai" subtitle="97 data">…</x-card>` | Judul opsional |
| `x-badge` | `<x-badge variant="success">Hadir</x-badge>` | `brand` · `info` · `accent` · `success` · `warning` · `danger` · `neutral` |
| `x-alert` | `<x-alert variant="danger" title="Gagal">…</x-alert>` | Pesan sukses/info/peringatan/error |
| `x-input` | `<x-input name="nip" invalid />` | `invalid` mengaktifkan gaya error |
| `x-table` | lihat §5 | Bersama `x-table.th` / `x-table.td` |
| `x-icon` | `<x-icon name="dashboard" />` | `dashboard` · `pegawai` · `departemen` · `jam` · `cuti` · `monitoring` · `pengajuan` · `laporan` · `konfigurasi` · `user` · `menu` · `logout`; ukuran `sm` · `md` · `lg` |
| `x-layouts.admin` | `<x-layouts.admin title="Pegawai">…</x-layouts.admin>` | Hanya untuk halaman admin — lihat §4.1 |

### Menambah komponen baru

1. Buat `resources/views/components/<nama>.blade.php` (komponen anonim — tidak perlu class PHP).
2. Pakai `@props([...])` untuk semua variasi, dan `match` untuk memetakan variasi ke daftar kelas.
3. Daftarkan di halaman `/dev/komponen` beserta seluruh varian.
4. Tulis di tabel §4 dokumen ini.
5. Buka PR — reviewer memastikan tidak ada hex literal dan tidak ada utility yang meniru komponen lain.

### 4.1 Layout halaman admin

Seluruh halaman admin dibungkus `<x-layouts.admin title="...">`
(`resources/views/components/layouts/admin.blade.php`) yang menyediakan **header,
sidebar menu, pesan sesi, dan footer**. Jangan menulis `<nav>`/`<header>` sendiri per halaman.

```blade
<x-layouts.admin title="Pegawai">
    <h1 class="text-xl font-semibold text-gray-900">Pegawai</h1>
    {{-- isi halaman --}}
</x-layouts.admin>
```

- **Menu sidebar didefinisikan sekali**, di array `$menu` dalam layout. Menambah menu =
  menambah **satu baris** di situ **dan** satu route dengan nama tersebut — tidak ada
  tautan yang bisa menyalah.
- Menu aktif ditandai otomatis dengan `aria-current="page"` lewat pola `admin.<menu>.*`,
  jadi ikut benar saat route `admin.pegawai.create` dan kawan-kawan menyusul.
- Pesan sesi `success`, `warning`, dan `error` otomatis tampil di atas konten
  (dipakai `RoleMiddleware` untuk peringatan "halaman ini hanya untuk akun …").
- Halaman yang belum diisi fiturnya ada di `resources/views/admin/*.blade.php` — tiap
  halaman menyebut issue yang akan mengisinya.

---

## 5. Tabel

Selalu lewat `x-table` + `x-table.th` / `x-table.td` supaya padding, warna header, dan perataan
identik di semua halaman.

```blade
<x-table>
    <thead>
        <tr>
            <x-table.th>NIP</x-table.th>
            <x-table.th>Nama</x-table.th>
            <x-table.th align="center">Status</x-table.th>
            <x-table.th align="right">Keterlambatan</x-table.th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
        <tr>
            <x-table.td>199302032022032001</x-table.td>
            <x-table.td>Budi Santoso</x-table.td>
            <x-table.td align="center"><x-badge variant="success">Hadir</x-badge></x-table.td>
            <x-table.td align="right">0 menit</x-table.td>
        </tr>
    </tbody>
</x-table>
```

---

## 6. Halaman referensi `/dev/komponen`

- Didaftarkan di `routes/web.php` **hanya saat bukan produksi** (`app()->isProduction()`).
- Menampilkan seluruh token warna + seluruh varian tiap komponen.
- **Sebelum menulis gaya baru, buka halaman ini dulu.** Kalau komponennya sudah ada, pakai.

---

## 7. Checklist review untuk PR frontend

- [ ] Tidak ada hex literal (`#xxxxxx`) di `.blade.php`
- [ ] Tidak ada utility yang meniru komponen yang sudah ada
- [ ] Halaman admin dibungkus `<x-layouts.admin>` (§4.1), menu sidebar tidak ditulis ulang
- [ ] Warna teks memakai step **700–950** (atau `gray-*`)
- [ ] Memakai `route()`, bukan URL literal (lihat `panduan-kolaborasi.md` §13)
- [ ] **Tidak ada kelas `dark:`** — dark mode tidak didukung (keputusan QA 23 Nov 2026)
- [ ] Validasi lewat FormRequest, tampilan error lewat `invalid` pada `x-input`
- [ ] `vendor/bin/pest` + `vendor/bin/pint` hijau

---

## 8. Belum diputuskan (perlu QA)

| Hal | Kondisi sekarang |
|---|---|
| **Urutan kelas otomatis** | `prettier` + `prettier-plugin-tailwindcss` belum dipasang — butuh **persetujuan ganti dependency**. Tanpa ini, urutan kelas masih tergantung orang. |
| **Layout admin (#23)** | Beralih dari Tabler ke Tailwind + komponen ini — lihat komentar di issue #23. |

## 9. Keputusan yang sudah disahkan

| Tanggal | Keputusan | Penjagaan |
|---|---|---|
| 23 Nov 2026 | **Dark mode dibuang seluruhnya.** Tidak ada kelas `dark:` di view mana pun. | Test `keeps dark mode out of every blade view` di `tests/Feature/DesignTokensTest.php` |
| 23 Nov 2026 | Stack Tailwind v4 + Blade component; shadcn & Tabler ditolak (§2) | — |
| 23 Nov 2026 | Reset password oleh admin, bukan email | `panduan-kolaborasi.md` §13 |
