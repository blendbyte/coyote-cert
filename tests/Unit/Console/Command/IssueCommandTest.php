<?php

use CoyoteCert\Console\Command\IssueCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;

use function Termwind\renderUsing;

beforeEach(function () {
    $this->buffer = new BufferedOutput();
    renderUsing($this->buffer);
});

afterEach(function () {
    renderUsing(null);
});

/**
 * Run the IssueCommand and return [statusCode, output].
 * Termwind is redirected to $this->buffer in beforeEach.
 *
 * @return array{0: int, 1: string}
 */
function runIssue(array $input): array
{
    $tester = new CommandTester(new IssueCommand());
    $tester->execute($input);

    return [$tester->getStatusCode(), test()->buffer->fetch()];
}

// ── Input validation ──────────────────────────────────────────────────────────

it('fails when no --domain is provided', function () {
    [$code, $output] = runIssue([]);

    expect($code)->toBe(Command::FAILURE);
    expect($output)->toContain('No domains specified');
});

it('fails when --webroot is missing', function () {
    [$code, $output] = runIssue(['--domain' => ['example.com']]);

    expect($code)->toBe(Command::FAILURE);
    expect($output)->toContain('--webroot is required');
});

it('fails for an unknown --provider', function () {
    [$code, $output] = runIssue([
        '--domain'   => ['example.com'],
        '--webroot'  => '/tmp',
        '--provider' => 'nonexistent-ca',
    ]);

    expect($code)->toBe(Command::FAILURE);
    expect($output)->toContain('nonexistent-ca');
});

it('fails for an unknown --key-type', function () {
    [$code, $output] = runIssue([
        '--domain'   => ['example.com'],
        '--webroot'  => '/tmp',
        '--key-type' => 'rsa9999',
    ]);

    expect($code)->toBe(Command::FAILURE);
    expect($output)->toContain('rsa9999');
});

it('fails for google provider without EAB credentials', function () {
    [$code, $output] = runIssue([
        '--domain'   => ['example.com'],
        '--webroot'  => '/tmp',
        '--provider' => 'google',
    ]);

    expect($code)->toBe(Command::FAILURE);
    expect($output)->toContain('eab-kid');
});

it('fails for sslcom provider without EAB credentials', function () {
    [$code, $output] = runIssue([
        '--domain'   => ['example.com'],
        '--webroot'  => '/tmp',
        '--provider' => 'sslcom',
    ]);

    expect($code)->toBe(Command::FAILURE);
    expect($output)->toContain('eab-kid');
});
