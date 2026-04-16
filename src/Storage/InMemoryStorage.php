<?php

namespace CoyoteCert\Storage;

use CoyoteCert\Enums\KeyType;
use CoyoteCert\Exceptions\LetsEncryptClientException;

/**
 * Volatile in-memory storage — useful for testing and one-shot scripts.
 * Nothing is persisted between requests.
 */
class InMemoryStorage implements StorageInterface
{
    private ?string  $accountKey     = null;
    private ?KeyType $accountKeyType = null;

    /** @var array<string, StoredCertificate> */
    private array $certificates = [];

    // ── Account key ──────────────────────────────────────────────────────────

    public function hasAccountKey(): bool
    {
        return $this->accountKey !== null;
    }

    public function getAccountKey(): string
    {
        if ($this->accountKey === null) {
            throw new LetsEncryptClientException('No account key in memory storage.');
        }

        return $this->accountKey;
    }

    public function getAccountKeyType(): KeyType
    {
        if ($this->accountKeyType === null) {
            throw new LetsEncryptClientException('No account key type in memory storage.');
        }

        return $this->accountKeyType;
    }

    public function saveAccountKey(string $pem, KeyType $type): void
    {
        $this->accountKey     = $pem;
        $this->accountKeyType = $type;
    }

    // ── Certificates ─────────────────────────────────────────────────────────

    public function hasCertificate(string $domain): bool
    {
        return isset($this->certificates[$domain]);
    }

    public function getCertificate(string $domain): ?StoredCertificate
    {
        return $this->certificates[$domain] ?? null;
    }

    public function saveCertificate(string $domain, StoredCertificate $cert): void
    {
        $this->certificates[$domain] = $cert;
    }
}
