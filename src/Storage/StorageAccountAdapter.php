<?php

namespace CoyoteCert\Storage;

use CoyoteCert\Enums\KeyType;
use CoyoteCert\Exceptions\CryptoException;
use CoyoteCert\Interfaces\AcmeAccountInterface;
use CoyoteCert\Support\OpenSsl;

/** @internal */
class StorageAccountAdapter implements AcmeAccountInterface
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly string           $providerSlug,
        private readonly KeyType          $keyType = KeyType::EC_P256,
    ) {}

    public function exists(): bool
    {
        return $this->storage->hasAccountKey($this->providerSlug);
    }

    public function getPrivateKey(): string
    {
        return $this->storage->getAccountKey($this->providerSlug);
    }

    public function getPublicKey(): string
    {
        $privateKey = openssl_pkey_get_private($this->getPrivateKey());

        if ($privateKey === false) {
            throw new CryptoException('Cannot load private key.');
        }

        $details = openssl_pkey_get_details($privateKey);

        if ($details === false) {
            throw new CryptoException('Failed to get key details.');
        }

        return $details['key'];
    }

    public function generateNewKeys(?KeyType $keyTypeOverride = null): bool
    {
        $keyType = $keyTypeOverride ?? $this->keyType;
        $key     = OpenSsl::generateKey($keyType);
        $pem     = OpenSsl::openSslKeyToString($key);
        $this->storage->saveAccountKey($this->providerSlug, $pem, $keyType);

        return true;
    }

    public function savePrivateKey(string $pem, KeyType $keyType): void
    {
        $this->storage->saveAccountKey($this->providerSlug, $pem, $keyType);
    }
}
