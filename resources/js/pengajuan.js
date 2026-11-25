/*
 * Form pengajuan (#36): tampilkan hanya field yang relevan.
 *
 * - Cuti     : jenis cuti + penanda haji.
 * - Dinas Luar: mode penuh/setengah.
 * - Sakit    : tidak butuh jenis cuti maupun mode DL.
 *
 * Field yang disembunyikan juga dinonaktifkan agar tidak ikut terkirim.
 */
const jenis = document.getElementById('jenis');
const blokJenisCuti = document.getElementById('blok-jenis-cuti');
const idCuti = document.getElementById('id_cuti');
const blokModeDl = document.getElementById('blok-mode-dl');
const modeDl = document.getElementById('mode_dl');
const blokAlasanHaji = document.getElementById('blok-alasan-haji');
const alasanHaji = document.getElementById('alasan_haji');

if (jenis) {
    const perbarui = () => {
        const pilihCuti = jenis.value === 'cuti';
        const pilihDl = jenis.value === 'dl';

        blokJenisCuti?.classList.toggle('hidden', !pilihCuti);

        if (idCuti) {
            idCuti.disabled = !pilihCuti;
            idCuti.required = pilihCuti;

            if (!pilihCuti) {
                idCuti.value = '';
            }
        }

        blokModeDl?.classList.toggle('hidden', !pilihDl);

        if (modeDl) {
            modeDl.disabled = !pilihDl;
            modeDl.required = pilihDl;

            if (!pilihDl) {
                modeDl.value = '';
            }
        }

        const cutiBesar = idCuti?.selectedOptions?.[0]?.dataset.kode === 'C04';
        const tampilkanHaji = pilihCuti && cutiBesar;

        blokAlasanHaji?.classList.toggle('hidden', !tampilkanHaji);

        if (alasanHaji) {
            alasanHaji.disabled = !tampilkanHaji;

            if (!tampilkanHaji) {
                alasanHaji.checked = false;
            }
        }
    };

    jenis.addEventListener('change', perbarui);
    idCuti?.addEventListener('change', perbarui);
    perbarui();
}
