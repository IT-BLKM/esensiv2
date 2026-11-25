import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

/*
 * Peta monitoring kehadiran admin (issue #41).
 *
 * Data titik dikirim server sebagai JSON di dalam #data-monitoring. Satu
 * marker dibuat untuk setiap koordinat masuk/pulang; garis putus-putus
 * menghubungkan keduanya bila ada. Tombol Peta pada tabel hanya memfokuskan
 * peta ke marker baris tersebut.
 */
const root = document.getElementById('halaman-monitoring');
const petaEl = document.getElementById('peta-monitoring');
const pesanEl = document.getElementById('pesan-peta-monitoring');
const dataEl = document.getElementById('data-monitoring');

if (root && petaEl) {
    const kantor = {
        latitude: Number(root.dataset.kantorLatitude),
        longitude: Number(root.dataset.kantorLongitude),
        radius: Number(root.dataset.kantorRadius),
    };

    let titik = [];

    try {
        titik = JSON.parse(dataEl?.textContent || '[]');
    } catch (error) {
        titik = [];
    }

    const penandaPerBaris = new Map();
    const koordinatPerBaris = new Map();
    let peta = null;

    function tampilkanPesan(teks) {
        if (!pesanEl) {
            return;
        }

        pesanEl.textContent = teks;
        pesanEl.classList.remove('hidden');
    }

    function buatPopup(data) {
        const isi = document.createElement('div');
        isi.className = 'text-sm';

        const nama = document.createElement('strong');
        nama.className = 'block text-gray-900';
        nama.textContent = data.nama;

        const nip = document.createElement('span');
        nip.className = 'block text-xs text-gray-600';
        nip.textContent = data.nip;

        const detail = document.createElement('span');
        detail.className = 'mt-1 block text-xs text-gray-700';
        detail.textContent = `${data.jenis === 'masuk' ? 'Masuk' : 'Pulang'} ${data.jam} · ${data.statusLabel}`;

        const koordinat = document.createElement('span');
        koordinat.className = 'mt-1 block text-xs text-gray-500';
        koordinat.textContent = `${Number(data.latitude).toFixed(6)}, ${Number(data.longitude).toFixed(6)}`;

        isi.append(nama, nip, detail, koordinat);

        return isi;
    }

    function fokuskanBaris(id) {
        if (!peta) {
            tampilkanPesan('Peta belum siap. Muat ulang halaman lalu coba lagi.');

            return;
        }

        const kunci = String(id);
        const penanda = penandaPerBaris.get(kunci) ?? [];
        const koordinat = koordinatPerBaris.get(kunci) ?? [];

        if (penanda.length === 0 || koordinat.length === 0) {
            tampilkanPesan('Baris ini tidak memiliki koordinat presensi.');

            return;
        }

        if (pesanEl) {
            pesanEl.classList.add('hidden');
        }

        if (koordinat.length > 1) {
            peta.fitBounds(koordinat, { padding: [40, 40], maxZoom: 18 });
        } else {
            peta.setView(koordinat[0], 18);
        }

        penanda[0].openPopup();
    }

    function gambarPeta() {
        try {
            peta = L.map(petaEl, { scrollWheelZoom: false }).setView(
                [kantor.latitude, kantor.longitude],
                16,
            );

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            }).addTo(peta);

            if (kantor.radius > 0) {
                L.circle([kantor.latitude, kantor.longitude], {
                    radius: kantor.radius,
                    className: 'peta-radius-kantor',
                }).addTo(peta);
            }

            const batas = [];

            for (const data of titik) {
                if (!Number.isFinite(Number(data.latitude)) || !Number.isFinite(Number(data.longitude))) {
                    continue;
                }

                const posisi = [Number(data.latitude), Number(data.longitude)];
                const penanda = L.circleMarker(posisi, {
                    radius: 7,
                    className: data.jenis === 'masuk' ? 'peta-titik-masuk' : 'peta-titik-keluar',
                }).addTo(peta);

                penanda.bindPopup(buatPopup(data));
                batas.push(posisi);

                const kunci = String(data.id);
                penandaPerBaris.set(kunci, [...(penandaPerBaris.get(kunci) ?? []), penanda]);
                koordinatPerBaris.set(kunci, [...(koordinatPerBaris.get(kunci) ?? []), posisi]);
            }

            for (const koordinat of koordinatPerBaris.values()) {
                if (koordinat.length < 2) {
                    continue;
                }

                L.polyline(koordinat, {
                    className: 'peta-garis-perjalanan',
                    weight: 2,
                    dashArray: '5 5',
                }).addTo(peta);
            }

            if (batas.length > 0) {
                peta.fitBounds(batas, { padding: [24, 24], maxZoom: 18 });
            } else {
                peta.setView([kantor.latitude, kantor.longitude], 16);
            }

            document.querySelectorAll('[data-fokus-presensi]').forEach((tombol) => {
                tombol.addEventListener('click', () => fokuskanBaris(tombol.dataset.fokusPresensi));
            });

            window.setTimeout(() => peta.invalidateSize(), 0);
        } catch (error) {
            petaEl.classList.add('hidden');
            tampilkanPesan('Peta tidak bisa dimuat. Tabel monitoring tetap bisa digunakan.');
        }
    }

    gambarPeta();
}
