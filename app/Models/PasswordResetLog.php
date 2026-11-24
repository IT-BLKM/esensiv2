<?php

namespace App\Models;

use Database\Factories\PasswordResetLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetLog extends Model
{
    /** @use HasFactory<PasswordResetLogFactory> */
    use HasFactory;

    /**
     * Nama tabel eksplisit mengikuti konvensi singular Database Esensi.
     */
    protected $table = 'password_reset_log';

    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'id_pegawai',
        'created_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }
}
