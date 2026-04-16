<?php

namespace CoyoteCert\Challenge;

use CoyoteCert\Enums\AuthorizationChallengeEnum;
use CoyoteCert\Exceptions\LetsEncryptClientException;
use CoyoteCert\Interfaces\ChallengeHandlerInterface;

/**
 * Deploys HTTP-01 challenges by writing token files into a webroot directory.
 *
 * The file is placed at:
 *   {webroot}/.well-known/acme-challenge/{token}
 *
 * with the content:
 *   {token}.{accountThumbprint}
 *
 * Make sure your web server serves files from this path without authentication.
 */
class Http01Handler implements ChallengeHandlerInterface
{
    public function __construct(private readonly string $webroot)
    {
    }

    public function supports(AuthorizationChallengeEnum $type): bool
    {
        return $type === AuthorizationChallengeEnum::HTTP;
    }

    public function deploy(string $domain, string $token, string $keyAuthorization): void
    {
        $dir = $this->challengeDir();

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new LetsEncryptClientException(
                sprintf('Could not create challenge directory "%s".', $dir)
            );
        }

        $path = $dir . $token;

        if (file_put_contents($path, $keyAuthorization) === false) {
            throw new LetsEncryptClientException(
                sprintf('Could not write challenge file "%s".', $path)
            );
        }
    }

    public function cleanup(string $domain, string $token): void
    {
        $path = $this->challengeDir() . $token;

        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function challengeDir(): string
    {
        return rtrim($this->webroot, '/') . '/.well-known/acme-challenge/';
    }
}
