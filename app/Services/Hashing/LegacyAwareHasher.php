<?php

namespace App\Services\Hashing;

use Illuminate\Contracts\Hashing\Hasher;

/**
 * Hash driver transisi: membuat hash baru dengan Argon2id, tetapi masih
 * memverifikasi hash bcrypt lama dari aplikasi sebelumnya.
 *
 * `needsRehash()` mengembalikan true untuk hash non-Argon2id sehingga
 * AuthController dapat melakukan rehash setelah login berhasil.
 */
class LegacyAwareHasher implements Hasher
{
    public function __construct(
        private readonly Hasher $primary,
        private readonly Hasher $legacy,
    ) {}

    /**
     * @return array
     */
    public function info($hashedValue)
    {
        return $this->primary->info($hashedValue);
    }

    /**
     * @param  string  $value
     * @return string
     */
    public function make(#[\SensitiveParameter] $value, array $options = [])
    {
        return $this->primary->make($value, $options);
    }

    /**
     * @param  string  $value
     * @param  string|null  $hashedValue
     */
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool
    {
        if ($hashedValue === null || (string) $hashedValue === '') {
            return false;
        }

        $algo = $this->primary->info($hashedValue)['algoName'] ?? null;

        return match ($algo) {
            'argon2id', 'argon2i' => $this->primary->check($value, $hashedValue, $options),
            'bcrypt' => $this->legacy->check($value, $hashedValue, $options),
            default => false,
        };
    }

    /**
     * @param  string|null  $hashedValue
     */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        if ($hashedValue === null || (string) $hashedValue === '') {
            return true;
        }

        $algo = $this->primary->info($hashedValue)['algoName'] ?? null;

        if ($algo !== 'argon2id') {
            return true;
        }

        return $this->primary->needsRehash($hashedValue, $options);
    }
}
