<?php

namespace CoyoteCert\DTO;

use CoyoteCert\Enums\EabAlgorithm;

readonly class EabCredentials
{
    public function __construct(
        public string       $kid,
        public string       $hmacKey,
        public EabAlgorithm $algorithm = EabAlgorithm::HS256,
    ) {
    }
}
