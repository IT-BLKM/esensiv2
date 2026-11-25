<?php

namespace App\Http\Requests;

use App\Models\Presensi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitoringKehadiranRequest extends FormRequest
{
    /**
     * Route admin + Policy viewAny sudah membatasi akses; request ini hanya
     * memvalidasi filter monitoring.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'id_dept' => ['nullable', 'integer', 'exists:departemen,id'],
            'status' => ['nullable', Rule::in(array_keys(Presensi::LABEL_STATUS))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'id_dept.integer' => 'Departemen tidak valid.',
            'id_dept.exists' => 'Departemen tidak ditemukan.',
            'status.in' => 'Status presensi tidak valid.',
        ];
    }
}
