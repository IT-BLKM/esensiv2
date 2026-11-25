<?php

namespace App\Http\Requests;

use App\Actions\Cuti\HitungSisaCutiAction;
use App\Actions\Pengajuan\HitungHariKerjaAction;
use App\Models\Cuti;
use App\Models\Pegawai;
use App\Models\Pengajuan;
use App\Models\Presensi;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pengajuan cuti, sakit, dan dinas luar dari form pegawai.
 *
 * Aturan id_cuti mencerminkan CHECK constraint di database:
 * jenis='cuti' ⇔ id_cuti terisi. Field kepemilikan dan approval sengaja
 * ditolak dari klien; nilainya ditentukan server di CreatePengajuanAction.
 */
class StorePengajuanRequest extends FormRequest
{
    public function __construct(
        private readonly HitungHariKerjaAction $hitungHariKerja,
        private readonly HitungSisaCutiAction $hitungSisaCuti,
    ) {}

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(['cuti', 'sakit', 'dl'])],
            'mode_dl' => [
                'nullable',
                Rule::in(['full', 'setengah']),
                'required_if:jenis,dl',
                'prohibited_if:jenis,cuti,sakit',
            ],
            'id_cuti' => [
                'nullable',
                'integer',
                'required_if:jenis,cuti',
                'exists:cuti,id',
                'prohibited_if:jenis,sakit,dl',
            ],
            'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
            'tanggal_berakhir' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['required', 'string', 'max:255'],
            'alasan_haji' => ['nullable', 'boolean'],
            'lampiran' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
                Rule::requiredIf(fn (): bool => $this->lampiranDibutuhkan() && ! $this->punyaLampiranTersimpan()),
            ],
            'id_pegawai' => ['prohibited'],
            'status' => ['prohibited'],
            'disetujui_oleh' => ['prohibited'],
            'disetujui_pada' => ['prohibited'],
            'catatan' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis pengajuan wajib dipilih.',
            'jenis.in' => 'Jenis pengajuan tidak valid.',
            'mode_dl.in' => 'Mode Dinas Luar tidak valid.',
            'mode_dl.required_if' => 'Mode Dinas Luar wajib dipilih untuk pengajuan Dinas Luar.',
            'mode_dl.prohibited_if' => 'Mode Dinas Luar hanya berlaku untuk pengajuan Dinas Luar.',
            'id_cuti.required_if' => 'Jenis cuti wajib dipilih untuk pengajuan cuti.',
            'id_cuti.exists' => 'Jenis cuti tidak ditemukan.',
            'id_cuti.prohibited' => 'Jenis cuti hanya berlaku untuk pengajuan cuti.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date_format' => 'Format tanggal mulai harus YYYY-MM-DD.',
            'tanggal_berakhir.required' => 'Tanggal berakhir wajib diisi.',
            'tanggal_berakhir.date_format' => 'Format tanggal berakhir harus YYYY-MM-DD.',
            'tanggal_berakhir.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'keterangan.string' => 'Keterangan harus berupa teks.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
            'alasan_haji.boolean' => 'Penanda alasan haji tidak valid.',
            'lampiran.required' => 'Lampiran wajib diunggah untuk sakit lebih dari 3 hari kerja dan cuti alasan penting.',
            'lampiran.file' => 'Lampiran harus berupa berkas.',
            'lampiran.mimes' => 'Lampiran harus berformat JPG, JPEG, PNG, atau PDF.',
            'lampiran.max' => 'Ukuran lampiran maksimal 2 MB.',
            'id_pegawai.prohibited' => 'Pegawai ditentukan dari akun yang sedang login.',
            'status.prohibited' => 'Status pengajuan ditentukan oleh sistem.',
            'disetujui_oleh.prohibited' => 'Data persetujuan ditentukan oleh sistem.',
            'disetujui_pada.prohibited' => 'Data persetujuan ditentukan oleh sistem.',
            'catatan.prohibited' => 'Catatan persetujuan ditentukan oleh sistem.',
        ];
    }

    /**
     * Cek tabrakan presensi hadir, tabrakan pengajuan approved, dan kecukupan
     * kuota setelah aturan dasar lolos. Pesan menyebut tanggal/kode yang
     * bermasalah agar pegawai bisa memperbaiki pengajuan tanpa menebak.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pegawai = $this->pegawaiPengajuan();

            if ($pegawai === null) {
                return;
            }

            $mulai = CarbonImmutable::parse((string) $this->input('tanggal_mulai'))->startOfDay();
            $selesai = CarbonImmutable::parse((string) $this->input('tanggal_berakhir'))->startOfDay();

            $tabrakan = Presensi::query()
                ->where('id_pegawai', $pegawai->id)
                ->where('status', 'hadir')
                ->whereBetween('tgl_presensi', [$mulai->toDateString(), $selesai->toDateString()])
                ->orderBy('tgl_presensi')
                ->first(['tgl_presensi']);

            if ($tabrakan !== null) {
                $tanggal = CarbonImmutable::parse((string) $tabrakan->tgl_presensi)->format('d/m/Y');
                $validator->errors()->add(
                    'tanggal_mulai',
                    "Sudah ada presensi hadir pada {$tanggal}. Pilih rentang tanggal lain."
                );

                return;
            }

            $bentrokPengajuan = Pengajuan::query()
                ->where('id_pegawai', $pegawai->id)
                ->where('status', 'approved')
                ->where('tanggal_mulai', '<=', $selesai->toDateString())
                ->where('tanggal_berakhir', '>=', $mulai->toDateString())
                ->orderBy('tanggal_mulai')
                ->first(['tanggal_mulai', 'tanggal_berakhir']);

            if ($bentrokPengajuan !== null) {
                $dari = CarbonImmutable::parse((string) $bentrokPengajuan->tanggal_mulai)->format('d/m/Y');
                $sampai = CarbonImmutable::parse((string) $bentrokPengajuan->tanggal_berakhir)->format('d/m/Y');
                $validator->errors()->add(
                    'tanggal_mulai',
                    "Rentang ini bertabrakan dengan pengajuan yang sudah disetujui ({$dari} – {$sampai})."
                );

                return;
            }

            if ($this->input('jenis') !== 'cuti' || $this->input('id_cuti') === null) {
                return;
            }

            /** @var Cuti|null $cuti */
            $cuti = Cuti::query()->find($this->input('id_cuti'));

            if ($cuti === null) {
                return;
            }

            $alasanHaji = $this->boolean('alasan_haji');

            if ($alasanHaji && $cuti->kode !== Cuti::KODE_BESAR) {
                $validator->errors()->add(
                    'alasan_haji',
                    'Penanda alasan haji hanya berlaku untuk Cuti Besar.'
                );

                return;
            }

            $hasil = ($this->hitungSisaCuti)($pegawai, $cuti, $mulai->year, $alasanHaji);

            if ($hasil['memenuhiSyarat'] === false) {
                $validator->errors()->add(
                    'id_cuti',
                    $hasil['alasanTidakMemenuhi'] ?? 'Syarat cuti ini belum terpenuhi.'
                );

                return;
            }

            $tersedia = $hasil['totalTersedia'];

            if ($tersedia === null) {
                return;
            }

            $hariDibutuhkan = ($this->hitungHariKerja)($pegawai, $mulai, $selesai);

            if ($hariDibutuhkan > $tersedia) {
                $validator->errors()->add(
                    'tanggal_berakhir',
                    "Sisa kuota {$cuti->nama} hanya {$tersedia} hari, sedangkan pengajuan ini membutuhkan {$hariDibutuhkan} hari kerja."
                );
            }
        });
    }

    /**
     * Lampiran sakit hanya wajib bila rentangnya lebih dari tiga hari kerja.
     * Hari kerja dihitung dengan aturan yang sama seperti approval (#39),
     * sehingga Sabtu/Minggu dan hari tanpa shift tidak menambah batas.
     */
    private function lampiranDibutuhkan(): bool
    {
        if ($this->input('jenis') === 'sakit') {
            return $this->jumlahHariKerjaSakit() > 3;
        }

        if ($this->input('jenis') !== 'cuti') {
            return false;
        }

        $idCuti = $this->input('id_cuti');

        if ($idCuti === null) {
            return false;
        }

        return Cuti::query()->whereKey($idCuti)->value('kode') === Cuti::KODE_ALASAN_PENTING;
    }

    private function jumlahHariKerjaSakit(): int
    {
        $pegawai = $this->pegawaiPengajuan();
        $mulai = $this->input('tanggal_mulai');
        $selesai = $this->input('tanggal_berakhir');

        if ($pegawai === null || ! is_string($mulai) || ! is_string($selesai)) {
            return 0;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $mulai) !== 1
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $selesai) !== 1) {
            return 0;
        }

        return ($this->hitungHariKerja)(
            $pegawai,
            CarbonImmutable::parse($mulai)->startOfDay(),
            CarbonImmutable::parse($selesai)->startOfDay(),
        );
    }

    /**
     * Pegawai yang menjadi subjek pengajuan. Form pegawai memakai pemilik akun;
     * form admin (issue #104) mengoverride untuk pegawai yang dipilih.
     */
    protected function pegawaiPengajuan(): ?Pegawai
    {
        return $this->user()?->pegawai;
    }

    /**
     * Saat mengedit pengajuan pending, lampiran lama dianggap memenuhi
     * kewajiban sampai pegawai mengunggah pengganti.
     */
    private function punyaLampiranTersimpan(): bool
    {
        $pengajuan = $this->route('pengajuan');

        return $pengajuan instanceof Pengajuan
            && is_string($pengajuan->lampiran)
            && $pengajuan->lampiran !== '';
    }
}
