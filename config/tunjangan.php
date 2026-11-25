<?php

/*
|--------------------------------------------------------------------------
| Tunjangan Kinerja — Tingkat Keterlambatan dan Pulang Sebelum Waktu
|--------------------------------------------------------------------------
|
| Mengikuti Lampiran I & II Permenkes RI No. 41 Tahun 2022. Persentase di
| bawah adalah potongan dari tunjangan kinerja, bukan nominal rupiah. Batas
| dibuat bersambung (1–30, 31–60, dst.) supaya tidak ada menit yang jatuh di
| celah antar tingkat.
|
| `min` dan `max` dalam menit. `max` null berarti tanpa batas atas.
|
*/

return [

    'keterlambatan' => [
        ['level' => 'TL1', 'min' => 1, 'max' => 30, 'persen' => 1.0],
        ['level' => 'TL2', 'min' => 31, 'max' => 60, 'persen' => 1.25],
        ['level' => 'TL3', 'min' => 61, 'max' => null, 'persen' => 2.5],
    ],

    'pulang_cepat' => [
        ['level' => 'PSW1', 'min' => 1, 'max' => 30, 'persen' => 0.5],
        ['level' => 'PSW2', 'min' => 31, 'max' => 60, 'persen' => 1.0],
        ['level' => 'PSW3', 'min' => 61, 'max' => 90, 'persen' => 1.25],
        ['level' => 'PSW4', 'min' => 91, 'max' => null, 'persen' => 2.5],
    ],

];
