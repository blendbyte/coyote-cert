<?php

namespace CoyoteCert\DTO;

use CoyoteCert\Enums\AuthorizationChallengeEnum;
use CoyoteCert\Http\Response;
use CoyoteCert\Support\Arr;

readonly class DomainValidationData
{
    public function __construct(
        public array $identifier,
        public string $status,
        public string $expires,
        public array $file,
        public array $dns,
        public array $dnsPersist,
        public array $validationRecord,
    ) {
    }

    public static function fromResponse(Response $response): DomainValidationData
    {
        $body       = $response->getBody();
        $challenges = $body['challenges'];

        return new self(
            identifier:       $body['identifier'],
            status:           $body['status'],
            expires:          $body['expires'],
            file:             self::getValidationByType($challenges, AuthorizationChallengeEnum::HTTP),
            dns:              self::getValidationByType($challenges, AuthorizationChallengeEnum::DNS),
            dnsPersist:       self::getValidationByType($challenges, AuthorizationChallengeEnum::DNS_PERSIST),
            validationRecord: Arr::get($body, 'validationRecord', []),
        );
    }

    private static function getValidationByType(array $haystack, AuthorizationChallengeEnum $authChallenge): array
    {
        foreach ($haystack as $key => $data) {
            if ($data['type'] === $authChallenge->value) {
                return $data;
            }
        }

        return [];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function hasErrors(): bool
    {
        foreach ([AuthorizationChallengeEnum::HTTP, AuthorizationChallengeEnum::DNS, AuthorizationChallengeEnum::DNS_PERSIST] as $type) {
            $data = $this->challengeData($type);
            if (!empty($data['error'])) {
                return true;
            }
        }

        return false;
    }

    public function getErrors(): array
    {
        if (!$this->hasErrors()) {
            return [];
        }

        $errors = [];

        foreach ([AuthorizationChallengeEnum::HTTP, AuthorizationChallengeEnum::DNS, AuthorizationChallengeEnum::DNS_PERSIST] as $type) {
            $data = $this->challengeData($type);
            if (!empty($data)) {
                $errors[] = [
                    'domainValidationType' => $type->value,
                    'error'                => Arr::get($data, 'error'),
                ];
            }
        }

        return $errors;
    }

    public function challengeData(AuthorizationChallengeEnum $type): array
    {
        return match ($type) {
            AuthorizationChallengeEnum::HTTP        => $this->file,
            AuthorizationChallengeEnum::DNS         => $this->dns,
            AuthorizationChallengeEnum::DNS_PERSIST => $this->dnsPersist,
        };
    }
}
