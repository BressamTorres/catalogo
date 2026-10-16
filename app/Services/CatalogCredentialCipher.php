<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

class CatalogCredentialCipher
{
    public function decryptToken(Business $business): string
    {
        $encryptedToken = (string) $business->catalog_api_token;

        if (
            $encryptedToken === ''
            || $business->catalog_api_token_expires_at === null
            || $business->catalog_api_token_expires_at->isPast()
        ) {
            throw new RuntimeException('A credencial do catálogo está ausente ou expirada.');
        }

        return $this->encrypter()->decryptString($encryptedToken);
    }

    public function encryptToken(string $token): string
    {
        return $this->encrypter()->encryptString($token);
    }

    private function encrypter(): Encrypter
    {
        $configuredKey = (string) config('services.mini_erp.credentials_key');
        $key = str_starts_with($configuredKey, 'base64:')
            ? base64_decode(substr($configuredKey, 7), true)
            : $configuredKey;

        if (! is_string($key) || ! Encrypter::supported($key, 'AES-256-CBC')) {
            throw new RuntimeException('CATALOG_CREDENTIAL_KEY deve possuir 32 bytes e usar o formato base64: recomendado.');
        }

        return new Encrypter($key, 'AES-256-CBC');
    }
}
