<?php

namespace App\Libraries;

final class StorageSyncSignature
{
    public static function sign(
        string $secret,
        string $clientId,
        string $method,
        string $path,
        string $timestamp,
        string $nonce,
        string $body,
    ): string {
        $canonical = implode("\n", [
            $clientId,
            strtoupper($method),
            $path,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);

        return hash_hmac('sha256', $canonical, $secret);
    }
}
