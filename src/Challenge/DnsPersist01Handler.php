<?php

namespace CoyoteCert\Challenge;

use CoyoteCert\Enums\AuthorizationChallengeEnum;
use CoyoteCert\Interfaces\ChallengeHandlerInterface;

/**
 * Base class for dns-persist-01 challenge handlers.
 *
 * Unlike dns-01, the TXT record is kept between renewals — the CA re-validates
 * against the existing record rather than requiring a new one each time. This
 * eliminates the DNS propagation wait on renewal.
 *
 * Extend this class and implement deploy() to set the TXT record at:
 *   _acme-challenge.{domain}  →  {keyAuthorization digest}
 *
 * cleanup() is intentionally a no-op: removing the record after each issuance
 * would defeat the purpose of the challenge type.
 *
 * Example:
 *
 *   class MyDnsPersist01Handler extends DnsPersist01Handler
 *   {
 *       public function deploy(string $domain, string $token, string $keyAuth): void
 *       {
 *           MyDnsProvider::setTxt('_acme-challenge.' . $domain, $keyAuth);
 *       }
 *   }
 */
abstract class DnsPersist01Handler implements ChallengeHandlerInterface
{
    final public function supports(AuthorizationChallengeEnum $type): bool
    {
        return $type === AuthorizationChallengeEnum::DNS_PERSIST;
    }

    abstract public function deploy(string $domain, string $token, string $keyAuthorization): void;

    /** No-op — the record is intentionally kept between renewals. */
    final public function cleanup(string $domain, string $token): void {}
}
