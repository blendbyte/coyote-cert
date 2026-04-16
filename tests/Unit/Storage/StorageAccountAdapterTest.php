<?php

use CoyoteCert\Enums\KeyType;
use CoyoteCert\Storage\InMemoryStorage;
use CoyoteCert\Storage\StorageAccountAdapter;

it('exists returns false when no account key is stored', function () {
    $storage = new InMemoryStorage();
    $adapter = new StorageAccountAdapter($storage);

    expect($adapter->exists())->toBeFalse();
});

it('generateNewKeys stores a key in storage', function () {
    $storage = new InMemoryStorage();
    $adapter = new StorageAccountAdapter($storage, KeyType::RSA_2048);

    $result = $adapter->generateNewKeys();

    expect($result)->toBeTrue();
    expect($storage->hasAccountKey())->toBeTrue();
    expect($storage->getAccountKeyType())->toBe(KeyType::RSA_2048);
});

it('exists returns true after generateNewKeys', function () {
    $storage = new InMemoryStorage();
    $adapter = new StorageAccountAdapter($storage, KeyType::RSA_2048);
    $adapter->generateNewKeys();

    expect($adapter->exists())->toBeTrue();
});

it('getPrivateKey returns the stored PEM', function () {
    $storage = new InMemoryStorage();
    $adapter = new StorageAccountAdapter($storage, KeyType::RSA_2048);
    $adapter->generateNewKeys();

    $pem = $adapter->getPrivateKey();
    expect($pem)->toBeString()->toContain('PRIVATE KEY');
});

it('getPublicKey returns a public key PEM', function () {
    $storage = new InMemoryStorage();
    $adapter = new StorageAccountAdapter($storage, KeyType::RSA_2048);
    $adapter->generateNewKeys();

    $pub = $adapter->getPublicKey();
    expect($pub)->toBeString()->toContain('PUBLIC KEY');
});
