<?php

namespace CoyoteCert\Storage;

use CoyoteCert\Enums\KeyType;
use CoyoteCert\Exceptions\LetsEncryptClientException;

class FilesystemStorage implements StorageInterface
{
    /**
     * @param string $directory Path where account keys and certificates are stored.
     */
    public function __construct(private readonly string $directory)
    {
    }

    // ── Account key ──────────────────────────────────────────────────────────

    public function hasAccountKey(): bool
    {
        return file_exists($this->accountKeyPath())
            && file_exists($this->accountMetaPath());
    }

    public function getAccountKey(): string
    {
        return $this->readFile($this->accountKeyPath());
    }

    public function getAccountKeyType(): KeyType
    {
        $meta = json_decode($this->readFile($this->accountMetaPath()), true, 512, JSON_THROW_ON_ERROR);

        return KeyType::from($meta['key_type']);
    }

    public function saveAccountKey(string $pem, KeyType $type): void
    {
        $this->ensureDirectory();
        $this->writeFile($this->accountKeyPath(), $pem);
        $this->writeFile(
            $this->accountMetaPath(),
            json_encode(['key_type' => $type->value], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    // ── Certificates ─────────────────────────────────────────────────────────

    public function hasCertificate(string $domain): bool
    {
        return file_exists($this->certPath($domain));
    }

    public function getCertificate(string $domain): ?StoredCertificate
    {
        if (!$this->hasCertificate($domain)) {
            return null;
        }

        $data = json_decode($this->readFile($this->certPath($domain)), true, 512, JSON_THROW_ON_ERROR);

        return StoredCertificate::fromArray($data);
    }

    public function saveCertificate(string $domain, StoredCertificate $cert): void
    {
        $this->ensureDirectory();
        $this->writeFile(
            $this->certPath($domain),
            json_encode($cert->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    // ── Paths ─────────────────────────────────────────────────────────────────

    private function accountKeyPath(): string
    {
        return $this->dir() . 'account.pem';
    }

    private function accountMetaPath(): string
    {
        return $this->dir() . 'account.json';
    }

    private function certPath(string $domain): string
    {
        // Sanitise the domain so it is safe to use as a filename.
        $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $domain);

        return $this->dir() . $safe . '.cert.json';
    }

    private function dir(): string
    {
        return rtrim($this->directory, '/') . '/';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function ensureDirectory(): void
    {
        $dir = $this->dir();

        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new LetsEncryptClientException(
                sprintf('Storage directory "%s" could not be created.', $dir)
            );
        }
    }

    private function readFile(string $path): string
    {
        if (!file_exists($path)) {
            throw new LetsEncryptClientException(
                sprintf('Storage file "%s" does not exist.', $path)
            );
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new LetsEncryptClientException(
                sprintf('Could not read storage file "%s".', $path)
            );
        }

        return $contents;
    }

    private function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new LetsEncryptClientException(
                sprintf('Could not write storage file "%s".', $path)
            );
        }

        // Restrict private key files to owner-read-only.
        if (str_ends_with($path, '.pem')) {
            chmod($path, 0600);
        }
    }
}
