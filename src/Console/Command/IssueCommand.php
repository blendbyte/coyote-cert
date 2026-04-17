<?php

namespace CoyoteCert\Console\Command;

use CoyoteCert\Challenge\Http01Handler;
use CoyoteCert\Console\ProviderResolver;
use CoyoteCert\CoyoteCert;
use CoyoteCert\Enums\KeyType;
use CoyoteCert\Exceptions\AuthException;
use CoyoteCert\Exceptions\RateLimitException;
use CoyoteCert\Storage\FilesystemStorage;
use CoyoteCert\Storage\StoredCertificate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;

#[AsCommand(name: 'issue', description: 'Issue or renew a TLS certificate')]
class IssueCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('domain',          'd', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Domain name(s) to include on the certificate')
            ->addOption('email',           'e', InputOption::VALUE_REQUIRED, 'Contact email for the ACME account')
            ->addOption('webroot',         'w', InputOption::VALUE_REQUIRED, 'Webroot path for HTTP-01 challenge (.well-known/acme-challenge will be written here)')
            ->addOption('provider',        'p', InputOption::VALUE_REQUIRED, 'CA to use: letsencrypt, letsencrypt-staging, zerossl, google, buypass, buypass-staging, sslcom', 'letsencrypt')
            ->addOption('storage',         's', InputOption::VALUE_REQUIRED, 'Directory to store certificates and account keys', './certs')
            ->addOption('days',            null, InputOption::VALUE_REQUIRED, 'Days before expiry to trigger renewal', '30')
            ->addOption('key-type',        null, InputOption::VALUE_REQUIRED, 'Certificate key type: ec256, ec384, rsa2048, rsa4096', 'ec256')
            ->addOption('force',           'f', InputOption::VALUE_NONE, 'Force issuance even if the certificate is still valid')
            ->addOption('skip-caa',        null, InputOption::VALUE_NONE, 'Skip CAA DNS pre-check')
            ->addOption('skip-local-test', null, InputOption::VALUE_NONE, 'Skip the pre-flight HTTP self-test')
            ->addOption('zerossl-key',     null, InputOption::VALUE_REQUIRED, 'ZeroSSL API key for automatic EAB provisioning')
            ->addOption('eab-kid',         null, InputOption::VALUE_REQUIRED, 'EAB key ID (Google Trust Services, SSL.com, or ZeroSSL pre-provisioned)')
            ->addOption('eab-hmac',        null, InputOption::VALUE_REQUIRED, 'EAB HMAC key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $domains  = $input->getOption('domain');
        $webroot  = $input->getOption('webroot');
        $provider = $input->getOption('provider');

        if (empty($domains)) {
            $this->renderError('No domains specified. Use --domain example.com (repeatable for SANs).');
            return Command::FAILURE;
        }

        if ($webroot === null) {
            $this->renderError('--webroot is required for HTTP-01 challenge validation.');
            return Command::FAILURE;
        }

        try {
            $acmeProvider = ProviderResolver::resolve(
                $provider,
                zeroSslKey: $input->getOption('zerossl-key'),
                eabKid: $input->getOption('eab-kid'),
                eabHmac: $input->getOption('eab-hmac'),
            );
        } catch (\InvalidArgumentException $e) {
            $this->renderError($e->getMessage());
            return Command::FAILURE;
        }

        try {
            $keyType = $this->resolveKeyType($input->getOption('key-type'));
        } catch (\InvalidArgumentException $e) {
            $this->renderError($e->getMessage());
            return Command::FAILURE;
        }

        $storagePath = $input->getOption('storage');
        $days        = max(1, (int) $input->getOption('days'));
        $force       = $input->getOption('force');

        $coyote = CoyoteCert::with($acmeProvider)
            ->storage(new FilesystemStorage($storagePath))
            ->identifiers($domains)
            ->challenge(new Http01Handler($webroot))
            ->keyType($keyType);

        if ($input->getOption('email')) {
            $coyote = $coyote->email($input->getOption('email'));
        }

        if ($input->getOption('skip-caa')) {
            $coyote = $coyote->skipCaaCheck();
        }

        if ($input->getOption('skip-local-test')) {
            $coyote = $coyote->skipLocalTest();
        }

        $wasIssued = false;
        $coyote->onIssued(function () use (&$wasIssued): void {
            $wasIssued = true;
        });

        $primaryDomain   = $domains[0];
        $providerDisplay = ProviderResolver::displayName($provider);

        $output->writeln(sprintf(
            '  Requesting certificate for <info>%s</info> via <comment>%s</comment>...',
            $primaryDomain,
            $providerDisplay,
        ));

        try {
            $cert = $force ? $coyote->issue() : $coyote->issueOrRenew($days);
        } catch (RateLimitException $e) {
            $retryAfter = $e->getRetryAfter();
            $hint       = $retryAfter !== null
                ? sprintf('Retry after: <b>%s</b>', $retryAfter->format('D, d M Y H:i:s T'))
                : 'Check your CA dashboard for rate limit details.';

            $this->renderError('Rate limit reached — too many certificate requests recently.', $hint);
            return Command::FAILURE;
        } catch (AuthException $e) {
            $this->renderError('Authentication failed.', htmlspecialchars($e->getMessage()));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->renderError('Certificate issuance failed.', htmlspecialchars($e->getMessage()));
            return Command::FAILURE;
        }

        $this->renderSuccess($cert, $storagePath, $providerDisplay, $wasIssued);

        return Command::SUCCESS;
    }

    private function renderSuccess(StoredCertificate $cert, string $storagePath, string $provider, bool $wasIssued): void
    {
        $icon    = $wasIssued ? '✓' : '→';
        $heading = $wasIssued ? 'Certificate issued successfully' : 'Certificate is still valid — no renewal needed';
        $color   = $wasIssued ? 'text-green-500' : 'text-blue-500';

        $domainsStr  = implode(', ', $cert->domains);
        $keyLabel    = $this->keyTypeLabel($cert->keyType);
        $expiresDate = $cert->expiresAt->format('M j, Y');
        $days        = $cert->remainingDays();
        $daysColor   = match (true) {
            $days <= 7  => 'text-red-500',
            $days <= 30 => 'text-yellow-500',
            default     => 'text-green-400',
        };

        render(sprintf(
            <<<HTML
            <div class="mt-1 mb-1">
                <div class="ml-2">
                    <span class="%s font-bold">%s</span>
                    <span class="ml-1 font-bold">%s</span>
                </div>
                <table class="mt-1 ml-4">
                    <tr>
                        <td class="text-gray-500 pr-4">Domain(s)</td>
                        <td>%s</td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 pr-4">Provider</td>
                        <td>%s</td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 pr-4">Key type</td>
                        <td>%s</td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 pr-4">Expires</td>
                        <td>%s <span class="%s">(%d days)</span></td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 pr-4">Storage</td>
                        <td>%s</td>
                    </tr>
                </table>
            </div>
            HTML,
            $color, $icon, $heading,
            $domainsStr,
            $provider,
            $keyLabel,
            $expiresDate, $daysColor, $days,
            $storagePath,
        ));
    }

    private function renderError(string $message, string $detail = ''): void
    {
        $detailHtml = $detail !== ''
            ? sprintf('<div class="ml-4 mt-1 text-red-400">%s</div>', $detail)
            : '';

        render(sprintf(
            <<<HTML
            <div class="mt-1 mb-1">
                <div class="ml-2">
                    <span class="text-red-500 font-bold">✗</span>
                    <span class="ml-1 text-red-500">%s</span>
                </div>
                %s
            </div>
            HTML,
            $message,
            $detailHtml,
        ));
    }

    private function resolveKeyType(string $type): KeyType
    {
        return match (strtolower($type)) {
            'ec256', 'ec-p256', 'p256' => KeyType::EC_P256,
            'ec384', 'ec-p384', 'p384' => KeyType::EC_P384,
            'rsa2048'                  => KeyType::RSA_2048,
            'rsa4096'                  => KeyType::RSA_4096,
            default                    => throw new \InvalidArgumentException(
                sprintf('Unknown key type "%s". Supported: ec256, ec384, rsa2048, rsa4096.', $type),
            ),
        };
    }

    private function keyTypeLabel(KeyType $type): string
    {
        return match ($type) {
            KeyType::EC_P256  => 'EC P-256',
            KeyType::EC_P384  => 'EC P-384',
            KeyType::RSA_2048 => 'RSA 2048',
            KeyType::RSA_4096 => 'RSA 4096',
        };
    }
}
