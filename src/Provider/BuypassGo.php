<?php

namespace CoyoteCert\Provider;

use CoyoteCert\DTO\EabCredentials;

class BuypassGo implements AcmeProviderInterface
{
    public function getDirectoryUrl(): string
    {
        return 'https://api.buypass.com/acme/directory';
    }

    public function getDisplayName(): string
    {
        return 'Buypass Go SSL';
    }

    public function isEabRequired(): bool
    {
        return false;
    }

    public function getEabCredentials(string $email): ?EabCredentials
    {
        return null;
    }

    public function verifyTls(): bool
    {
        return true;
    }
}
