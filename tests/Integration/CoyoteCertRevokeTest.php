<?php

use CoyoteCert\CoyoteCert;
use CoyoteCert\Storage\InMemoryStorage;
use Tests\Integration\Helpers\NoOpHttp01Handler;

it('revokes a previously issued certificate', function () {
    $storage = new InMemoryStorage();

    $cert = CoyoteCert::with(pebble())
        ->storage($storage)
        ->domains('revoke.example.com')
        ->challenge(new NoOpHttp01Handler())
        ->skipLocalTest()
        ->issue();

    $result = CoyoteCert::with(pebble())
        ->storage($storage)
        ->revoke($cert);

    expect($result)->toBeTrue();
})->skip(!getenv('PEBBLE_URL'), 'Set PEBBLE_URL to run Pebble integration tests');

it('revokes with a specific reason code', function () {
    $storage = new InMemoryStorage();

    $cert = CoyoteCert::with(pebble())
        ->storage($storage)
        ->domains('revoke-reason.example.com')
        ->challenge(new NoOpHttp01Handler())
        ->skipLocalTest()
        ->issue();

    $result = CoyoteCert::with(pebble())
        ->storage($storage)
        ->revoke($cert, reason: 1); // keyCompromise

    expect($result)->toBeTrue();
})->skip(!getenv('PEBBLE_URL'), 'Set PEBBLE_URL to run Pebble integration tests');

it('throws when revoke is called without storage', function () {
    $storage = new InMemoryStorage();

    $cert = CoyoteCert::with(pebble())
        ->storage($storage)
        ->domains('revoke-nostorage.example.com')
        ->challenge(new NoOpHttp01Handler())
        ->skipLocalTest()
        ->issue();

    expect(fn () => CoyoteCert::with(pebble())->revoke($cert))
        ->toThrow(\CoyoteCert\Exceptions\LetsEncryptClientException::class);
})->skip(!getenv('PEBBLE_URL'), 'Set PEBBLE_URL to run Pebble integration tests');
