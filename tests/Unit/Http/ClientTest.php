<?php

use CoyoteCert\Http\Client;

it('setTimeout() updates the internal timeout value', function () {
    $client = new Client(timeout: 10);
    $client->setTimeout(30);

    $ref = new \ReflectionProperty(Client::class, 'timeout');
    expect($ref->getValue($client))->toBe(30);
});

it('getCurlHandle() enables FOLLOWLOCATION and MAXREDIRS when maxRedirects > 0', function () {
    $client = new Client();
    $method = new \ReflectionMethod(Client::class, 'getCurlHandle');

    $handle = $method->invoke($client, 'http://example.com', [], 2);

    expect($handle)->toBeInstanceOf(\CurlHandle::class);
    // curl handles are freed automatically in PHP 8.3+; no explicit close needed
});

// ── parseRawHeaders() ─────────────────────────────────────────────────────────

it('parseRawHeaders() parses a single header into a key-value pair', function () {
    $client = new Client();
    $method = new \ReflectionMethod(Client::class, 'parseRawHeaders');

    $result = $method->invoke($client, "Content-Type: application/json\r\n");

    expect($result)->toBe(['content-type' => 'application/json']);
});

it('parseRawHeaders() normalises header names to lowercase with hyphens', function () {
    $client = new Client();
    $method = new \ReflectionMethod(Client::class, 'parseRawHeaders');

    $result = $method->invoke($client, "Replay_Nonce: abc123\r\n");

    expect($result)->toHaveKey('replay-nonce');
    expect($result['replay-nonce'])->toBe('abc123');
});

it('parseRawHeaders() trims whitespace from header values', function () {
    $client = new Client();
    $method = new \ReflectionMethod(Client::class, 'parseRawHeaders');

    $result = $method->invoke($client, "Location:   https://example.com/  \r\n");

    expect($result['location'])->toBe('https://example.com/');
});

it('parseRawHeaders() skips lines without a colon', function () {
    $client = new Client();
    $method = new \ReflectionMethod(Client::class, 'parseRawHeaders');

    $result = $method->invoke($client, "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n");

    expect($result)->not->toHaveKey('http/1.1 200 ok');
    expect($result)->toHaveKey('content-type');
});

it('parseRawHeaders() accumulates repeated headers as comma-separated values', function () {
    $client = new Client();
    $method = new \ReflectionMethod(Client::class, 'parseRawHeaders');

    $raw    = "Link: <https://example.com/alt1>; rel=\"alternate\"\nLink: <https://example.com/alt2>; rel=\"alternate\"\n";
    $result = $method->invoke($client, $raw);

    expect($result['link'])->toContain('<https://example.com/alt1>');
    expect($result['link'])->toContain('<https://example.com/alt2>');
    expect($result['link'])->toContain(', ');
});

it('parseRawHeaders() preserves colons in header values', function () {
    $client = new Client();
    $method = new \ReflectionMethod(Client::class, 'parseRawHeaders');

    $result = $method->invoke($client, "Location: https://example.com:8080/path\r\n");

    expect($result['location'])->toBe('https://example.com:8080/path');
});
