<?php

namespace CoyoteCert\Interfaces;

use CoyoteCert\Enums\AuthorizationChallengeEnum;

interface ChallengeHandlerInterface
{
    /**
     * Returns true if this handler can deploy the given challenge type.
     */
    public function supports(AuthorizationChallengeEnum $type): bool;

    /**
     * Deploy the challenge so it can be verified by the CA.
     *
     * @param string $domain          The domain being validated.
     * @param string $token           The challenge token (filename for http-01, TXT value seed for dns-01).
     * @param string $keyAuthorization The full key authorization string (token + "." + thumbprint).
     */
    public function deploy(string $domain, string $token, string $keyAuthorization): void;

    /**
     * Remove the challenge after validation is complete (success or failure).
     */
    public function cleanup(string $domain, string $token): void;
}
