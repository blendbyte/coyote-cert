<?php

namespace CoyoteCert\Endpoints;

use CoyoteCert\Exceptions\AcmeException;
use CoyoteCert\Http\Response;

class Directory extends Endpoint
{
    public function all(): Response
    {
        $response = $this->client
            ->getHttpClient()
            ->get($this->client->getProvider()->getDirectoryUrl());

        if ($response->getHttpResponseCode() >= 400) {
            $this->logResponse('error', 'Cannot get directory', $response);

            throw new AcmeException('Cannot get directory');
        }

        return $response;
    }

    public function newNonce(): string
    {
        return $this->all()->jsonBody()['newNonce'];
    }

    public function newAccount(): string
    {
        return $this->all()->jsonBody()['newAccount'];
    }

    public function newOrder(): string
    {
        return $this->all()->jsonBody()['newOrder'];
    }

    public function getOrder(): string
    {
        $url = str_replace('new-order', 'order', $this->newOrder());

        return rtrim($url, '/') . '/';
    }

    public function revoke(): string
    {
        return $this->all()->jsonBody()['revokeCert'];
    }

    public function renewalInfo(): ?string
    {
        return $this->all()->jsonBody()['renewalInfo'] ?? null;
    }

    public function keyChange(): string
    {
        return $this->all()->jsonBody()['keyChange'];
    }
}
