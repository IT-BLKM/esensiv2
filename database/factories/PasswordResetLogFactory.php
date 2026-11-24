<?php

namespace Database\Factories;

use App\Models\PasswordResetLog;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PasswordResetLog>
 */
class PasswordResetLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->state(['role' => 'admin']),
            'id_pegawai' => Pegawai::factory(),
            'created_at' => now(),
        ];
    }
}
