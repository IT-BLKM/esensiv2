<?php

namespace App\Http\Requests;

use App\Models\Cuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSaldoCutiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maksBawa = max(0, (int) (Cuti::query()
            ->where('kode', Cuti::KODE_TAHUNAN)
            ->value('maks_bawa') ?? 6));

        return [
            'saldo_tahun_lalu' => ['required', 'integer', 'min:0', "max:{$maksBawa}"],
            'saldo_dua_tahun_lalu' => ['required', 'integer', 'min:0', "max:{$maksBawa}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maksBawa = max(0, (int) (Cuti::query()
            ->where('kode', Cuti::KODE_TAHUNAN)
            ->value('maks_bawa') ?? 6));

        return [
            'saldo_tahun_lalu.required' => 'Saldo tahun lalu wajib diisi.',
            'saldo_tahun_lalu.integer' => 'Saldo tahun lalu harus berupa angka bulat.',
            'saldo_tahun_lalu.min' => 'Saldo tahun lalu tidak boleh negatif.',
            'saldo_tahun_lalu.max' => "Saldo tahun lalu maksimal {$maksBawa} hari.",
            'saldo_dua_tahun_lalu.required' => 'Saldo dua tahun lalu wajib diisi.',
            'saldo_dua_tahun_lalu.integer' => 'Saldo dua tahun lalu harus berupa angka bulat.',
            'saldo_dua_tahun_lalu.min' => 'Saldo dua tahun lalu tidak boleh negatif.',
            'saldo_dua_tahun_lalu.max' => "Saldo dua tahun lalu maksimal {$maksBawa} hari.",
        ];
    }
}
