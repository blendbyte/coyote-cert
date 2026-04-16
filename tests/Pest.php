<?php

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Integration');

// ── Shared key fixtures (pre-generated to avoid macOS EC key generation issues) ──

function rsaKeyPem(): string
{
    openssl_pkey_export(
        openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]),
        $pem
    );
    return $pem;
}

function ecKeyPem(string $curve = 'prime256v1'): string
{
    return match ($curve) {
        'prime256v1' => "-----BEGIN EC PRIVATE KEY-----\nMHcCAQEEIHaR0sCEL8isElEhAhPAsqrogUVVqP+uvX8Bf9zsjALqoAoGCCqGSM49\nAwEHoUQDQgAEN2q6j/MaE8CZ6KLmpR5ocW26YOXvVgiuIuIpouGek2Bu67BBpDRs\nG17vInzVc/P0R01RhthIrIahxR2OdxbkZw==\n-----END EC PRIVATE KEY-----",
        'secp384r1'  => "-----BEGIN EC PRIVATE KEY-----\nMIGkAgEBBDDgub3rNdQD28MtMUkOsFxxDIlS5mzPotXUzl/5IQLTd0oGtNdbovij\nV6H+2jzWT66gBwYFK4EEACKhZANiAAR+uI186ZeIR46EbYd7XRLWI4fotezzHLUS\noaF73Sp236v453E4W/V7QnMevfA3WtLnrhb7F1IATQLGO4f1skqmMSqHYXzRSLOW\nCejQifvrz0TqrkyVdK9e7uq36NPEDDw=\n-----END EC PRIVATE KEY-----",
        'secp521r1'  => "-----BEGIN EC PRIVATE KEY-----\nMIHcAgEBBEIBn7Elzxkr+b9LEKfx/wxC7/g+hqiiI+OsrXp4CGNOgiCy+B6yQFI8\nuUB41kdrTzsd0YFnDhiKkx256WDxap2rEs6gBwYFK4EEACOhgYkDgYYABADV+WWz\neq1sbiBK5IJkT4AcV14E8tw8h2uE7Oz3RHF//MoGQlAeZJZ2a/e5nrzbCxVV8ySz\nNsWw/Ye7ErDbvPZb6gCxUemjdn7hVHrnbqoDgDJXlcSI0QtSHQcb3C9ifjxCqhvl\nhzyCoKJdVpqaJk8ArxBh1sLbDLrXREZyXseGAWjteQ==\n-----END EC PRIVATE KEY-----",
        default      => throw new \InvalidArgumentException("Unknown curve: $curve"),
    };
}
