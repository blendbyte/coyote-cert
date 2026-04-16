<?php

use CoyoteCert\Challenge\Http01Handler;
use CoyoteCert\Enums\AuthorizationChallengeEnum;
use CoyoteCert\Exceptions\LetsEncryptClientException;

beforeEach(function () {
    $this->webroot = sys_get_temp_dir() . '/coyote-http01-' . uniqid();
    $this->handler = new Http01Handler($this->webroot);
});

afterEach(function () {
    $dir = $this->webroot . '/.well-known/acme-challenge';
    if (is_dir($dir)) {
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
        @rmdir($this->webroot . '/.well-known');
        @rmdir($this->webroot);
    }
});

it('supports the http-01 challenge type', function () {
    expect($this->handler->supports(AuthorizationChallengeEnum::HTTP))->toBeTrue();
});

it('does not support dns-01', function () {
    expect($this->handler->supports(AuthorizationChallengeEnum::DNS))->toBeFalse();
});

it('does not support dns-persist-01', function () {
    expect($this->handler->supports(AuthorizationChallengeEnum::DNS_PERSIST))->toBeFalse();
});

it('deploy creates the challenge file', function () {
    $this->handler->deploy('example.com', 'tokenABC', 'tokenABC.thumbprint');

    $path = $this->webroot . '/.well-known/acme-challenge/tokenABC';
    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toBe('tokenABC.thumbprint');
});

it('deploy creates the challenge directory when it does not exist', function () {
    expect(is_dir($this->webroot))->toBeFalse();

    $this->handler->deploy('example.com', 'tok', 'content');

    expect(is_dir($this->webroot . '/.well-known/acme-challenge'))->toBeTrue();
});

it('deploy works with a trailing slash in webroot', function () {
    $handler = new Http01Handler($this->webroot . '/');
    $handler->deploy('example.com', 'tok2', 'c2');

    $path = $this->webroot . '/.well-known/acme-challenge/tok2';
    expect(file_exists($path))->toBeTrue();
});

it('cleanup removes the challenge file', function () {
    $this->handler->deploy('example.com', 'tokenXYZ', 'content');

    $path = $this->webroot . '/.well-known/acme-challenge/tokenXYZ';
    expect(file_exists($path))->toBeTrue();

    $this->handler->cleanup('example.com', 'tokenXYZ');
    expect(file_exists($path))->toBeFalse();
});

it('cleanup is a no-op when the file does not exist', function () {
    expect(fn () => $this->handler->cleanup('example.com', 'nonexistent'))->not->toThrow(\Throwable::class);
});
