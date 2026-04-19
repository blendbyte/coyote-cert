<?php

namespace CoyoteCert\Storage;

use CoyoteCert\Enums\KeyType;

interface StorageInterface
{
    // ── Account key ──────────────────────────────────────────────────────────

    public function hasAccountKey(string $providerSlug): bool;

    /** Returns the account private key PEM for the given provider. */
    public function getAccountKey(string $providerSlug): string;

    /** Returns the KeyType that was used when the account key was saved. */
    public function getAccountKeyType(string $providerSlug): KeyType;

    public function saveAccountKey(string $providerSlug, string $pem, KeyType $type): void;

    // ── Certificates ─────────────────────────────────────────────────────────

    /**
     * @param string $domain Primary domain (used as the storage key).
     * @param KeyType $keyType The key algorithm for the certificate to look up.
     */
    public function hasCertificate(string $domain, KeyType $keyType): bool;

    public function getCertificate(string $domain, KeyType $keyType): ?StoredCertificate;

    /**
     * Persist a certificate. The storage key is derived from the primary domain
     * plus the key type carried inside $cert.
     */
    public function saveCertificate(string $domain, StoredCertificate $cert): void;

    /**
     * Remove a stored certificate. No-op when the domain/key-type is not found.
     */
    public function deleteCertificate(string $domain, KeyType $keyType): void;
}
