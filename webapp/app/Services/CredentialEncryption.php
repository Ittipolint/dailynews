<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class CredentialEncryption
{
    public static function encrypt(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return array_map(
            fn ($value) => is_string($value) && $value !== '' ? Crypt::encryptString($value) : $value,
            $data
        );
    }

    public static function decrypt(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return array_map(function ($value) {
            if (! is_string($value) || $value === '') {
                return $value;
            }

            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $value;
            }
        }, $data);
    }
}
