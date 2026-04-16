<?php

use CoyoteCert\Support\CryptRSA;

it('generates a private and public RSA key pair', function () {
    $keys = CryptRSA::generate();

    expect($keys)->toHaveKeys(['privateKey', 'publicKey']);
    expect($keys['privateKey'])->toContain('PRIVATE KEY');
    expect($keys['publicKey'])->toContain('PUBLIC KEY');
});

it('generated private key is parseable by openssl', function () {
    $keys = CryptRSA::generate();
    $pkey = openssl_pkey_get_private($keys['privateKey']);

    expect($pkey)->not->toBeFalse();
    expect(openssl_pkey_get_details($pkey)['bits'])->toBe(4096);
});
