<?php

namespace CoyoteCert\Exceptions;

class DomainValidationException extends AcmeException
{
    public static function localHttpChallengeTestFailed(string $domain, string $code): self
    {
        return new self(sprintf(
            'The local HTTP challenge test for %s received an invalid response with a %s status code.',
            $domain,
            $code,
        ));
    }

    /**
     * @param array<string> $found
     */
    public static function localDnsChallengeTestFailed(
        string $domain,
        string $record = '',
        string $nameserver = '',
        string $expected = '',
        array $found = [],
        ?string $lookupError = null,
    ): self {
        $base = sprintf("Couldn't fetch the correct DNS records for %s.", $domain);

        if ($record === '') {
            return new self($base);
        }

        if ($lookupError !== null) {
            return new self(sprintf('%s DNS lookup failed: %s', $base, $lookupError));
        }

        $foundStr = empty($found)
            ? '(none)'
            : implode(', ', array_map(fn($v) => '"' . $v . '"', $found));

        return new self(sprintf(
            '%s Queried %s TXT at %s — expected: "%s" — found: %s',
            $base,
            $record,
            $nameserver,
            $expected,
            $foundStr,
        ));
    }
}
