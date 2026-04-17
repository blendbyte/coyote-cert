<?php

use CoyoteCert\Console\ProviderResolver;
use CoyoteCert\Provider\BuypassGo;
use CoyoteCert\Provider\BuypassGoStaging;
use CoyoteCert\Provider\GoogleTrustServices;
use CoyoteCert\Provider\LetsEncrypt;
use CoyoteCert\Provider\LetsEncryptStaging;
use CoyoteCert\Provider\SslCom;
use CoyoteCert\Provider\ZeroSSL;

// ── resolve() ─────────────────────────────────────────────────────────────────

it('resolves letsencrypt', function () {
    expect(ProviderResolver::resolve('letsencrypt'))->toBeInstanceOf(LetsEncrypt::class);
});

it('resolves letsencrypt via le alias', function () {
    expect(ProviderResolver::resolve('le'))->toBeInstanceOf(LetsEncrypt::class);
});

it('resolves letsencrypt-staging', function () {
    expect(ProviderResolver::resolve('letsencrypt-staging'))->toBeInstanceOf(LetsEncryptStaging::class);
});

it('resolves letsencrypt-staging via le-staging alias', function () {
    expect(ProviderResolver::resolve('le-staging'))->toBeInstanceOf(LetsEncryptStaging::class);
});

it('resolves letsencrypt-staging via staging alias', function () {
    expect(ProviderResolver::resolve('staging'))->toBeInstanceOf(LetsEncryptStaging::class);
});

it('resolves zerossl', function () {
    expect(ProviderResolver::resolve('zerossl'))->toBeInstanceOf(ZeroSSL::class);
});

it('resolves zerossl with an api key', function () {
    expect(ProviderResolver::resolve('zerossl', zeroSslKey: 'mykey'))->toBeInstanceOf(ZeroSSL::class);
});

it('resolves zerossl with pre-provisioned EAB', function () {
    expect(ProviderResolver::resolve('zerossl', eabKid: 'kid', eabHmac: 'hmac'))->toBeInstanceOf(ZeroSSL::class);
});

it('resolves google', function () {
    expect(ProviderResolver::resolve('google', eabKid: 'k', eabHmac: 'h'))->toBeInstanceOf(GoogleTrustServices::class);
});

it('resolves google via google-trust-services alias', function () {
    expect(ProviderResolver::resolve('google-trust-services', eabKid: 'k', eabHmac: 'h'))->toBeInstanceOf(GoogleTrustServices::class);
});

it('resolves google via gts alias', function () {
    expect(ProviderResolver::resolve('gts', eabKid: 'k', eabHmac: 'h'))->toBeInstanceOf(GoogleTrustServices::class);
});

it('resolves sslcom', function () {
    expect(ProviderResolver::resolve('sslcom', eabKid: 'k', eabHmac: 'h'))->toBeInstanceOf(SslCom::class);
});

it('resolves sslcom via ssl.com alias', function () {
    expect(ProviderResolver::resolve('ssl.com', eabKid: 'k', eabHmac: 'h'))->toBeInstanceOf(SslCom::class);
});

it('resolves buypass', function () {
    expect(ProviderResolver::resolve('buypass'))->toBeInstanceOf(BuypassGo::class);
});

it('resolves buypass-staging', function () {
    expect(ProviderResolver::resolve('buypass-staging'))->toBeInstanceOf(BuypassGoStaging::class);
});

it('is case-insensitive', function () {
    expect(ProviderResolver::resolve('LetsEncrypt'))->toBeInstanceOf(LetsEncrypt::class);
    expect(ProviderResolver::resolve('ZEROSSL'))->toBeInstanceOf(ZeroSSL::class);
});

it('throws for an unknown provider', function () {
    ProviderResolver::resolve('unknownca');
})->throws(\InvalidArgumentException::class);

it('throws for google without eab-kid', function () {
    ProviderResolver::resolve('google', eabHmac: 'hmac');
})->throws(\InvalidArgumentException::class, 'eab-kid');

it('throws for google without eab-hmac', function () {
    ProviderResolver::resolve('google', eabKid: 'kid');
})->throws(\InvalidArgumentException::class, 'eab-hmac');

it('throws for sslcom without EAB credentials', function () {
    ProviderResolver::resolve('sslcom');
})->throws(\InvalidArgumentException::class, 'eab-kid');

// ── displayName() ──────────────────────────────────────────────────────────────

it('returns display name for letsencrypt', function () {
    expect(ProviderResolver::displayName('letsencrypt'))->toBe("Let's Encrypt");
});

it('returns display name for letsencrypt-staging', function () {
    expect(ProviderResolver::displayName('letsencrypt-staging'))->toContain('Staging');
});

it('returns display name for zerossl', function () {
    expect(ProviderResolver::displayName('zerossl'))->toBe('ZeroSSL');
});

it('returns display name for google', function () {
    expect(ProviderResolver::displayName('google'))->toContain('Google');
});

it('returns display name for buypass', function () {
    expect(ProviderResolver::displayName('buypass'))->toContain('Buypass');
});

it('returns display name for sslcom', function () {
    expect(ProviderResolver::displayName('sslcom'))->toContain('SSL');
});

it('falls back to the raw name for unknown providers', function () {
    expect(ProviderResolver::displayName('customca'))->toBe('customca');
});
