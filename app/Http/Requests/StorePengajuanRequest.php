<?php

namespace App\Http\Requests;

use App\Actions\Pengajuan\HitungHariKerjaAction;
use App\Actions\Pengajuan\HitungSisaCutiAction;
use App\Models\Cuti;
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
    /** Kode master Cuti Alasan Penting yang mewajibkan lampiran. */
    private const KODE_ALASAN_PENTING = 'C03';

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
            'lampiran' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
                Rule::requiredIf(fn (): bool => $this->lampiranDibutuhkan()),
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
            'lampiran.required' => 'Lampiran wajib diunggah untuk sakit dan cuti alasan penting.',
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
     * Cek tabrakan presensi hadir dan kecukupan kuota setelah aturan dasar
     * lolos. Pesan menyebut tanggal/kode yang bermasalah agar pegawai bisa
     * memperbaiki pengajuan tanpa menebak.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pegawai = $this->user()?->pegawai;

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

            if ($this->input('jenis') !== 'cuti' || $this->input('id_cuti') === null) {
                return;
            }

            /** @var Cuti|null $cuti */
            $cuti = Cuti::query()->find($this->input('id_cuti'));

            if ($cuti === null || $cuti->kuota === null) {
                return;
            }

            $sisa = ($this->hitungSisaCuti)($pegawai, $cuti, $mulai->year);

            if ($sisa === null) {
                return;
            }

            $hariDibutuhkan = ($this->hitungHariKerja)($pegawai, $mulai, $selesai);

            if ($hariDibutuhkan > $sisa) {
                $validator->errors()->add(
                    'tanggal_berakhir',
                    "Sisa kuota {$cuti->nama} hanya {$sisa} hari, sedangkan pengajuan ini membutuhkan {$hariDibutuhkan} hari kerja."
                );
            }
        });
    }

    private function lampiranDibutuhkan(): bool
    {
        if ($this->input('jenis') === 'sakit') {
            return true;
        }

        if ($this->input('jenis') !== 'cuti') {
            return false;
        }

        $idCuti = $this->input('id_cuti');

        if ($idCuti === null) {
            return false;
        }

        return Cuti::query()->whereKey($idCuti)->value('kode') === self::KODE_ALASAN_PENTING;
    }
}
