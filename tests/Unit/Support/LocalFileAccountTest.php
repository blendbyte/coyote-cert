<?php

use CoyoteCert\Enums\KeyType;
use CoyoteCert\Exceptions\StorageException;
use CoyoteCert\Support\LocalFileAccount;

beforeEach(function () {
    $this->dir     = sys_get_temp_dir() . '/coyote-lfa-' . uniqid();
    $this->account = new LocalFileAccount($this->dir);
});

afterEach(function () {
    if (is_dir($this->dir)) {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }
});

it('exists returns false when no keys are stored', function () {
    expect($this->account->exists())->toBeFalse();
});

it('generateNewKeys creates private and public key files', function () {
    expect($this->account->generateNewKeys())->toBeTrue();
    expect($this->account->exists())->toBeTrue();
});

it('generateNewKeys creates an EC key when KeyType::EC_P256 is passed', function () {
    expect($this->account->generateNewKeys(KeyType::EC_P256))->toBeTrue();
    expect($this->account->exists())->toBeTrue();
    expect($this->account->getPrivateKey())->toContain('PRIVATE KEY');
});

it('getPrivateKey returns a PEM string after key generation', function () {
    $this->account->generateNewKeys();
    expect($this->account->getPrivateKey())->toContain('PRIVATE KEY');
});

it('getPublicKey returns a PEM string after key generation', function () {
    $this->account->generateNewKeys();
    expect($this->account->getPublicKey())->toContain('PUBLIC KEY');
});

it('getPrivateKey throws when no key has been generated', function () {
    expect(fn () => $this->account->getPrivateKey())
        ->toThrow(StorageException::class);
});

it('getPublicKey throws when no key has been generated', function () {
    expect(fn () => $this->account->getPublicKey())
        ->toThrow(StorageException::class);
});

it('trailing slash is normalised in the path', function () {
    $account = new LocalFileAccount($this->dir . '/');
    $account->generateNewKeys();
    expect($account->exists())->toBeTrue();
});

it('exists returns false when the directory exists but key files are absent', function () {
    mkdir($this->dir, 0755, true);
    expect($this->account->exists())->toBeFalse();
});

it('generateNewKeys throws when the directory cannot be created', function () {
    // Create a FILE at the path so mkdir inside it fails
    file_put_contents($this->dir, 'not-a-dir');

    expect(fn () => $this->account->generateNewKeys())
        ->toThrow(StorageException::class, 'was not created');

    @unlink($this->dir);
});
