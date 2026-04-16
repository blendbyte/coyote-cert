<?php

namespace CoyoteCert;

use Psr\Log\LoggerInterface;
use CoyoteCert\Endpoints\Account;
use CoyoteCert\Endpoints\Certificate;
use CoyoteCert\Endpoints\Directory;
use CoyoteCert\Endpoints\DomainValidation;
use CoyoteCert\Endpoints\Nonce;
use CoyoteCert\Endpoints\Order;
use CoyoteCert\Exceptions\LetsEncryptClientException;
use CoyoteCert\Http\Client;
use CoyoteCert\Interfaces\AcmeAccountInterface;
use CoyoteCert\Interfaces\HttpClientInterface;

class Api
{
    private const PRODUCTION_URL = 'https://acme-v02.api.letsencrypt.org';
    private const STAGING_URL = 'https://acme-staging-v02.api.letsencrypt.org';

    public function __construct(
        bool $staging = false,
        private ?AcmeAccountInterface $localAccount = null,
        private ?LoggerInterface $logger = null,
        private ?HttpClientInterface $httpClient = null,
        private ?string $baseUrl = null,
    ) {
        if (empty($this->baseUrl)) {
            $this->baseUrl = $staging ? self::STAGING_URL : self::PRODUCTION_URL;
        }
    }

    public function setLocalAccount(AcmeAccountInterface $account): self
    {
        $this->localAccount = $account;

        return $this;
    }

    public function localAccount(): AcmeAccountInterface
    {
        if ($this->localAccount === null) {
            throw new LetsEncryptClientException('No account set.');
        }

        return $this->localAccount;
    }

    public function directory(): Directory
    {
        return new Directory($this);
    }

    public function nonce(): Nonce
    {
        return new Nonce($this);
    }

    public function account(): Account
    {
        return new Account($this);
    }

    public function order(): Order
    {
        return new Order($this);
    }

    public function domainValidation(): DomainValidation
    {
        return new DomainValidation($this);
    }

    public function certificate(): Certificate
    {
        return new Certificate($this);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHttpClient(): HttpClientInterface
    {
        // Create a default client if none is set.
        if ($this->httpClient === null) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    public function setHttpClient(HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function logger(string $level, string $message, array $context = []): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message, $context);
        }
    }
}
