<?php

namespace CoyoteCert\Provider;

use CoyoteCert\DTO\EabCredentials;

interface AcmeProviderInterface
{
    /**
     * The full ACME directory URL (e.g. https://acme-v02.api.letsencrypt.org/directory).
     */
    public function getDirectoryUrl(): string;

    /**
     * Human-readable name of the CA (used in logs and error messages).
     */
    public function getDisplayName(): string;

    /**
     * Whether this CA requires External Account Binding on registration.
     */
    public function isEabRequired(): bool;

    /**
     * Return EAB credentials for the given email address, or null if the caller
     * must supply them manually (e.g. Google Trust Services).
     *
     * For ZeroSSL this auto-provisions credentials via their REST API.
     * For CAs without EAB this always returns null.
     */
    public function getEabCredentials(string $email): ?EabCredentials;

    /**
     * Whether to verify the CA's TLS certificate.
     * Should only be false for local Pebble test instances.
     */
    public function verifyTls(): bool;
}
