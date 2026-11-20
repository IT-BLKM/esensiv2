<?php

namespace App\Models;

use Database\Factories\SaldoCutiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaldoCuti extends Model
{
    /** @use HasFactory<SaldoCutiFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit sesuai spesifikasi Database Esensi.
     */
    protected $table = 'saldo_cuti';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pegawai',
        'id_cuti',
        'tahun_sumber',
        'saldo',
    ];
}
