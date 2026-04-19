<?php

namespace CoyoteCert\Storage;

use CoyoteCert\Enums\KeyType;
use CoyoteCert\Exceptions\StorageException;

class DatabaseStorage implements StorageInterface
{
    public function __construct(
        private readonly \PDO   $pdo,
        private readonly string $table = 'coyote_cert_storage',
    ) {
        self::validateIdentifier($this->table);
    }

    /**
     * Returns the SQL statement that creates the storage table.
     * Execute this once during your application's setup / migration.
     *
     * Supports MySQL/MariaDB, PostgreSQL, and SQLite.
     */
    public static function createTableSql(string $table = 'coyote_cert_storage'): string
    {
        self::validateIdentifier($table);

        return <<<SQL
            CREATE TABLE IF NOT EXISTS `{$table}` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_key`  VARCHAR(255)  NOT NULL,
                `value`      MEDIUMTEXT    NOT NULL,
                `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_store_key` (`store_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            SQL;
    }

    // ── Account key ──────────────────────────────────────────────────────────

    public function hasAccountKey(string $providerSlug): bool
    {
        return $this->get('account:' . $providerSlug . ':pem') !== null;
    }

    public function getAccountKey(string $providerSlug): string
    {
        return $this->get('account:' . $providerSlug . ':pem')
            ?? throw new StorageException('No account key found in database storage.');
    }

    public function getAccountKeyType(string $providerSlug): KeyType
    {
        $value = $this->get('account:' . $providerSlug . ':key_type')
            ?? throw new StorageException('No account key type found in database storage.');

        return KeyType::from($value);
    }

    public function saveAccountKey(string $providerSlug, string $pem, KeyType $type): void
    {
        $this->set('account:' . $providerSlug . ':pem', $pem);
        $this->set('account:' . $providerSlug . ':key_type', $type->value);
    }

    // ── Certificates ─────────────────────────────────────────────────────────

    public function hasCertificate(string $domain, KeyType $keyType): bool
    {
        return $this->get($this->certKey($domain, $keyType)) !== null;
    }

    public function getCertificate(string $domain, KeyType $keyType): ?StoredCertificate
    {
        $json = $this->get($this->certKey($domain, $keyType));

        return $json !== null
            ? StoredCertificate::fromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR))
            : null;
    }

    public function saveCertificate(string $domain, StoredCertificate $cert): void
    {
        $this->set(
            $this->certKey($domain, $cert->keyType),
            json_encode($cert->toArray(), JSON_THROW_ON_ERROR),
        );
    }

    public function deleteCertificate(string $domain, KeyType $keyType): void
    {
        $this->pdo
            ->prepare("DELETE FROM `{$this->table}` WHERE `store_key` = :key")
            ->execute([':key' => $this->certKey($domain, $keyType)]);
    }

    // ── PDO helpers ───────────────────────────────────────────────────────────

    private function certKey(string $domain, KeyType $keyType): string
    {
        return 'cert:' . $domain . ':' . $keyType->value;
    }

    private function get(string $key): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT `value` FROM `{$this->table}` WHERE `store_key` = :key LIMIT 1",
        );
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row !== false ? (string) $row['value'] : null;
    }

    private function set(string $key, string $value): void
    {
        $sql = match ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            'sqlite' => "INSERT OR REPLACE INTO `{$this->table}` (`store_key`, `value`) VALUES (:key, :value)",
            'pgsql'  => "INSERT INTO \"{$this->table}\" (\"store_key\", \"value\")
                         VALUES (:key, :value)
                         ON CONFLICT (\"store_key\") DO UPDATE SET \"value\" = EXCLUDED.\"value\"",
            default => "INSERT INTO `{$this->table}` (`store_key`, `value`)
                         VALUES (:key, :value)
                         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
        };

        $this->pdo->prepare($sql)->execute([':key' => $key, ':value' => $value]);
    }

    private static function validateIdentifier(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid SQL identifier "%s": only [a-zA-Z0-9_] are allowed.', $name),
            );
        }
    }
}
