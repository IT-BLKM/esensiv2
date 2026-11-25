import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/*
 * Halaman presensi (#31) + penyimpanan ke server (#32).
 *
 * Urutan kerja:
 * 1. Model deteksi wajah dimuat dari public/models sejak halaman dibuka.
 * 2. Kamera baru diminta setelah pengguna menekan "Nyalakan kamera".
 * 3. Lokasi diminta setelah pengguna menekan "Ambil lokasi".
 * 4. Tombol absen hanya aktif bila shift ada, kamera siap, lokasi siap, dan
 *    wajah terdeteksi. Bila model gagal dimuat atau melewati batas waktu,
 *    absen tetap bisa dilakukan (fallback issue #35) dengan pesan yang jelas.
 *
 * Model: `tiny_face_detector` dari @vladmandic/face-api, disalin ke
 * public/models agar tidak bergantung CDN saat runtime.
 *
 * Penyimpanan ke server ditangani `presensi.store` (StorePresensiAction,
 * issue #32). Bila server menerima, halaman dimuat ulang agar status
 * masuk/keluar ikut berubah.
 */

const BATAS_MUAT_MODEL_MS = 10000;

const root = document.getElementById('halaman-presensi');

if (root) {
    mulaiPresensi(root);
}

function mulaiPresensi(root) {
    const bisaAbsen = root.dataset.bisaAbsen === '1';
    const kantor = {
        latitude: Number(root.dataset.kantorLatitude),
        longitude: Number(root.dataset.kantorLongitude),
        radius: Number(root.dataset.kantorRadius),
    };

    const video = document.getElementById('video-presensi');
    const placeholder = document.getElementById('placeholder-kamera');
    const tombolKamera = document.getElementById('tombol-kamera');
    const statusWajah = document.getElementById('status-wajah');
    const pesanKamera = document.getElementById('pesan-kamera');
    const pesanModel = document.getElementById('pesan-model');
    const tombolLokasi = document.getElementById('tombol-lokasi');
    const pesanLokasi = document.getElementById('pesan-lokasi');
    const petaEl = document.getElementById('peta-presensi');
    const teksKoordinat = document.getElementById('teks-koordinat');
    const teksJarak = document.getElementById('teks-jarak');
    const teksAkurasi = document.getElementById('teks-akurasi');
    const teksRadius = document.getElementById('teks-radius');
    const form = document.getElementById('form-presensi');
    const tombolAbsen = document.getElementById('tombol-absen');
    const alasanTombol = document.getElementById('alasan-tombol');
    const pesanPresensi = document.getElementById('pesan-presensi');
    const inputFoto = document.getElementById('input-foto');
    const inputKoordinat = document.getElementById('input-koordinat');
    const inputAkurasi = document.getElementById('input-akurasi');
    const inputJenis = document.getElementById('input-jenis');
    const inputKonfirmasiPulangCepat = document.getElementById('input-konfirmasi-pulang-cepat');

    const state = {
        faceapi: null,
        modelSiap: false,
        modelGagal: false,
        kameraSiap: false,
        kameraDiminta: false,
        wajahTerdeteksi: false,
        lokasiSiap: false,
        koordinat: null,
        akurasi: null,
        peta: null,
        penanda: null,
        lingkaranAkurasi: null,
        deteksiTimer: null,
        galatDeteksi: 0,
        sibuk: false,
    };

    function tampilkan(el, teks) {
        if (!el) {
            return;
        }

        el.textContent = teks;
        el.classList.remove('hidden');
    }

    function sembunyikan(el) {
        el?.classList.add('hidden');
    }

    /*
     * Jam kantor bisa berbeda dengan zona waktu browser. Bandingkan "HH:MM"
     * pada zona waktu kantor agar konfirmasi pulang cepat tidak salah waktu.
     */
    function jamSekarangDiZona() {
        try {
            return new Intl.DateTimeFormat('en-GB', {
                timeZone: root.dataset.zonaWaktu || 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            }).format(new Date());
        } catch (error) {
            return '';
        }
    }

    function perluKonfirmasiPulangCepat() {
        const jamPulang = root.dataset.jamPulang;

        if (!jamPulang || inputJenis?.value !== 'keluar') {
            return false;
        }

        const sekarang = jamSekarangDiZona();

        return sekarang !== '' && sekarang < jamPulang;
    }

    function perbaruiStatusWajah() {
        if (!statusWajah) {
            return;
        }

        if (!state.kameraSiap) {
            statusWajah.textContent = 'Kamera belum aktif.';

            return;
        }

        if (!state.modelSiap && !state.modelGagal) {
            statusWajah.textContent = 'Memuat model deteksi wajah...';

            return;
        }

        if (state.modelGagal) {
            statusWajah.textContent = 'Deteksi wajah tidak tersedia — absen tetap bisa dilakukan.';

            return;
        }

        statusWajah.textContent = state.wajahTerdeteksi
            ? 'Wajah terdeteksi.'
            : 'Arahkan wajah ke kamera.';
    }

    function perbaruiTombol() {
        if (!tombolAbsen) {
            return;
        }

        if (!bisaAbsen) {
            tombolAbsen.disabled = true;

            return;
        }

        const wajahSiap = state.modelGagal || state.wajahTerdeteksi;
        const alasan = [];

        if (!state.kameraSiap) {
            alasan.push('nyalakan kamera');
        }

        if (!state.lokasiSiap) {
            alasan.push('ambil lokasi');
        }

        if (!wajahSiap) {
            alasan.push(state.modelGagal ? 'deteksi wajah tidak tersedia' : 'hadapkan wajah');
        }

        const siap = alasan.length === 0;

        tombolAbsen.disabled = !siap;

        if (alasanTombol) {
            alasanTombol.textContent = siap
                ? 'Semua siap. Tekan tombol untuk mengirim presensi.'
                : 'Lengkapi dulu: '.concat(alasan.join(', ')).concat('.');
        }
    }

    function denganBatasWaktu(janji, milidetik) {
        return new Promise((selesai, gagal) => {
            const timer = window.setTimeout(
                () => gagal(new Error('Waktu memuat model deteksi wajah habis.')),
                milidetik,
            );

            janji.then(
                (hasil) => {
                    window.clearTimeout(timer);
                    selesai(hasil);
                },
                (error) => {
                    window.clearTimeout(timer);
                    gagal(error);
                },
            );
        });
    }

    async function muatModel() {
        try {
            const faceapi = await denganBatasWaktu(
                import('@vladmandic/face-api'),
                BATAS_MUAT_MODEL_MS,
            );

            await denganBatasWaktu(
                faceapi.nets.tinyFaceDetector.loadFromUri(root.dataset.modelsUrl),
                BATAS_MUAT_MODEL_MS,
            );

            state.faceapi = faceapi;
            state.modelSiap = true;
        } catch (error) {
            state.modelGagal = true;
            pesanModel?.classList.remove('hidden');
        }

        perbaruiStatusWajah();
        perbaruiTombol();
    }

    function pesanKameraGagal(error) {
        if (error?.name === 'NotAllowedError') {
            return 'Izin kamera ditolak. Izinkan akses kamera di pengaturan browser lalu muat ulang halaman.';
        }

        if (error?.name === 'NotFoundError') {
            return 'Kamera tidak ditemukan. Pastikan perangkat memiliki kamera yang aktif.';
        }

        if (error?.name === 'NotReadableError') {
            return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
        }

        return 'Kamera tidak bisa dinyalakan. Periksa izin browser lalu coba lagi.';
    }

    async function nyalakanKamera() {
        if (state.kameraSiap || state.kameraDiminta) {
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            tampilkan(pesanKamera, 'Browser ini tidak mendukung akses kamera.');

            return;
        }

        state.kameraDiminta = true;
        tombolKamera.disabled = true;
        tombolKamera.textContent = 'Menyalakan kamera...';

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
                audio: false,
            });

            video.srcObject = stream;
            await video.play();

            state.kameraSiap = true;
            placeholder?.classList.add('hidden');
            tombolKamera.classList.add('hidden');
            sembunyikan(pesanKamera);
            perbaruiStatusWajah();
            perbaruiTombol();
            jadwalkanDeteksi();
        } catch (error) {
            state.kameraDiminta = false;
            tombolKamera.disabled = false;
            tombolKamera.textContent = 'Nyalakan kamera';
            tampilkan(pesanKamera, pesanKameraGagal(error));
        }
    }

    function jadwalkanDeteksi() {
        if (state.deteksiTimer) {
            window.clearTimeout(state.deteksiTimer);
        }

        state.deteksiTimer = window.setTimeout(deteksiWajah, 600);
    }

    async function deteksiWajah() {
        if (state.modelGagal) {
            return;
        }

        if (!state.modelSiap || !state.kameraSiap || !state.faceapi || document.hidden) {
            jadwalkanDeteksi();

            return;
        }

        try {
            if (video.readyState >= 2 && video.videoWidth > 0) {
                const hasil = await state.faceapi.detectSingleFace(
                    video,
                    new state.faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }),
                );
                const terdeteksi = Boolean(hasil);

                state.galatDeteksi = 0;

                if (terdeteksi !== state.wajahTerdeteksi) {
                    state.wajahTerdeteksi = terdeteksi;
                    perbaruiStatusWajah();
                    perbaruiTombol();
                }
            }
        } catch (error) {
            /*
             * Error sesaat (mis. video belum siap) diabaikan. Bila terus
             * berulang, deteksi dianggap tidak tersedia agar tombol absen tidak
             * terkunci selamanya — fallback issue #35.
             */
            state.galatDeteksi++;

            if (state.galatDeteksi >= 5) {
                state.modelGagal = true;
                state.wajahTerdeteksi = false;
                pesanModel?.classList.remove('hidden');
                perbaruiStatusWajah();
                perbaruiTombol();

                return;
            }
        }

        jadwalkanDeteksi();
    }

    function jarakMeter(lokasi, tujuan) {
        const radiusBumi = 6371000;
        const rad = (derajat) => (derajat * Math.PI) / 180;
        const dLat = rad(tujuan.latitude - lokasi.latitude);
        const dLng = rad(tujuan.longitude - lokasi.longitude);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(rad(lokasi.latitude)) * Math.cos(rad(tujuan.latitude)) * Math.sin(dLng / 2) ** 2;

        return 2 * radiusBumi * Math.asin(Math.sqrt(a));
    }

    function formatJarak(meter) {
        return meter >= 1000
            ? `${(meter / 1000).toFixed(2)} km`
            : `${Math.round(meter)} m`;
    }

    function gambarPeta() {
        if (!state.koordinat || !petaEl) {
            return;
        }

        try {
            if (!state.peta) {
                state.peta = L.map(petaEl, { scrollWheelZoom: false }).setView(
                    [kantor.latitude, kantor.longitude],
                    16,
                );

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                }).addTo(state.peta);

                L.circle([kantor.latitude, kantor.longitude], {
                    radius: kantor.radius,
                    className: 'peta-radius-kantor',
                }).addTo(state.peta);
            }

            if (state.penanda) {
                state.penanda.setLatLng([state.koordinat.latitude, state.koordinat.longitude]);
            } else {
                state.penanda = L.circleMarker(
                    [state.koordinat.latitude, state.koordinat.longitude],
                    { radius: 8, className: 'peta-lokasi-pegawai' },
                ).addTo(state.peta);
            }

            if (state.lingkaranAkurasi) {
                state.lingkaranAkurasi.setLatLng([state.koordinat.latitude, state.koordinat.longitude]);
                state.lingkaranAkurasi.setRadius(state.akurasi ?? 0);
            } else {
                state.lingkaranAkurasi = L.circle(
                    [state.koordinat.latitude, state.koordinat.longitude],
                    { radius: state.akurasi ?? 0, className: 'peta-akurasi-pegawai' },
                ).addTo(state.peta);
            }

            state.peta.fitBounds(
                L.latLngBounds([
                    [kantor.latitude, kantor.longitude],
                    [state.koordinat.latitude, state.koordinat.longitude],
                ]),
                { padding: [24, 24], maxZoom: 18 },
            );
        } catch (error) {
            petaEl.classList.add('hidden');
            tampilkan(pesanLokasi, 'Peta tidak bisa dimuat. Indikator teks tetap tersedia.');
        }
    }

    function pesanLokasiGagal(error) {
        if (error?.code === error?.PERMISSION_DENIED) {
            return 'Izin lokasi ditolak. Izinkan akses lokasi untuk melanjutkan presensi.';
        }

        if (error?.code === error?.POSITION_UNAVAILABLE) {
            return 'Lokasi tidak tersedia. Pastikan GPS aktif lalu coba lagi.';
        }

        if (error?.code === error?.TIMEOUT) {
            return 'Pengambilan lokasi terlalu lama. Coba lagi di area dengan sinyal lebih baik.';
        }

        return 'Lokasi tidak bisa diambil. Periksa izin lokasi lalu coba lagi.';
    }

    function ambilLokasi() {
        if (!navigator.geolocation) {
            tampilkan(pesanLokasi, 'Browser ini tidak mendukung geolokasi.');

            return;
        }

        tombolLokasi.disabled = true;
        tombolLokasi.textContent = 'Mengambil lokasi...';

        navigator.geolocation.getCurrentPosition(
            (posisi) => {
                state.koordinat = {
                    latitude: posisi.coords.latitude,
                    longitude: posisi.coords.longitude,
                };
                state.akurasi = posisi.coords.accuracy;
                state.lokasiSiap = true;

                const jarak = jarakMeter(state.koordinat, kantor);
                const dalamRadius = jarak <= kantor.radius;

                if (teksKoordinat) {
                    teksKoordinat.textContent = `${state.koordinat.latitude.toFixed(6)}, ${state.koordinat.longitude.toFixed(6)}`;
                }

                if (teksJarak) {
                    teksJarak.textContent = `${formatJarak(jarak)} (radius ${formatJarak(kantor.radius)})`;
                }

                if (teksAkurasi) {
                    teksAkurasi.textContent = `±${Math.round(state.akurasi)} m`;
                }

                if (teksRadius) {
                    teksRadius.textContent = dalamRadius ? 'Di dalam radius kantor' : 'Di luar radius kantor';
                }

                inputKoordinat.value = `${state.koordinat.latitude},${state.koordinat.longitude}`;
                inputAkurasi.value = String(Math.round(state.akurasi));

                sembunyikan(pesanLokasi);
                gambarPeta();
                perbaruiTombol();

                tombolLokasi.disabled = false;
                tombolLokasi.textContent = 'Perbarui lokasi';
            },
            (error) => {
                tampilkan(pesanLokasi, pesanLokasiGagal(error));
                tombolLokasi.disabled = false;
                tombolLokasi.textContent = 'Ambil lokasi';
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
        );
    }

    function ambilFoto() {
        if (!state.kameraSiap || video.videoWidth === 0) {
            return null;
        }

        const kanvas = document.createElement('canvas');
        kanvas.width = video.videoWidth;
        kanvas.height = video.videoHeight;

        const konteks = kanvas.getContext('2d');
        konteks.drawImage(video, 0, 0, kanvas.width, kanvas.height);

        return kanvas.toDataURL('image/jpeg', 0.85);
    }

    async function kirimPresensi(event) {
        event.preventDefault();

        if (!bisaAbsen || state.sibuk) {
            return;
        }

        if (state.modelSiap && !state.wajahTerdeteksi) {
            tampilkan(pesanPresensi, 'Wajah belum terdeteksi. Arahkan wajah ke kamera lalu coba lagi.');

            return;
        }

        if (!state.lokasiSiap) {
            tampilkan(pesanPresensi, 'Lokasi belum diambil. Tekan "Ambil lokasi" lalu coba lagi.');

            return;
        }

        if (inputKonfirmasiPulangCepat) {
            inputKonfirmasiPulangCepat.value = '0';
        }

        if (perluKonfirmasiPulangCepat()) {
            const jamPulang = root.dataset.jamPulang;
            const yakin = window.confirm(
                `Sekarang ${jamSekarangDiZona()}, sedangkan jam pulang shift ${jamPulang}. `
                + 'Jika tetap absen pulang sekarang, presensi tercatat pulang lebih awal '
                + 'dan dapat dikenakan potongan PSW. Lanjutkan?',
            );

            if (!yakin) {
                return;
            }

            if (inputKonfirmasiPulangCepat) {
                inputKonfirmasiPulangCepat.value = '1';
            }
        }

        const foto = ambilFoto();

        if (foto === null) {
            tampilkan(pesanPresensi, 'Foto belum bisa diambil. Pastikan kamera menyala lalu coba lagi.');

            return;
        }

        inputFoto.value = foto;
        state.sibuk = true;
        tombolAbsen.disabled = true;

        try {
            const jawaban = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const hasil = await jawaban.json().catch(() => ({}));
            const kesalahanValidasi = hasil.errors
                ? Object.values(hasil.errors).flat()[0]
                : null;

            tampilkan(
                pesanPresensi,
                kesalahanValidasi
                    ?? hasil.message
                    ?? (jawaban.ok
                        ? 'Presensi berhasil dicatat.'
                        : 'Presensi belum bisa diproses. Coba lagi beberapa saat lagi.'),
            );

            if (jawaban.ok) {
                window.setTimeout(() => window.location.reload(), 1200);
            }
        } catch (error) {
            tampilkan(pesanPresensi, 'Tidak bisa menghubungi server. Periksa koneksi lalu coba lagi.');
        } finally {
            state.sibuk = false;
            perbaruiTombol();
        }
    }

    tombolKamera?.addEventListener('click', nyalakanKamera);
    tombolLokasi?.addEventListener('click', ambilLokasi);
    form?.addEventListener('submit', kirimPresensi);

    window.addEventListener('pagehide', () => {
        if (state.deteksiTimer) {
            window.clearTimeout(state.deteksiTimer);
        }

        video?.srcObject?.getTracks().forEach((track) => track.stop());
    });

    muatModel();
    perbaruiTombol();
}
