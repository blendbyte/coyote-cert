<?php

namespace CoyoteCert\Console\Command;

use CoyoteCert\Enums\KeyType;
use CoyoteCert\Storage\FilesystemStorage;
use CoyoteCert\Storage\StoredCertificate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;

#[AsCommand(name: 'certs', description: 'List all stored certificates')]
class ListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('storage', 's', InputOption::VALUE_REQUIRED, 'Directory where certificates are stored', './certs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storagePath = (string) $input->getOption('storage');
        $dir         = rtrim($storagePath, '/') . '/';

        if (!is_dir($dir)) {
            render(sprintf(
                <<<HTML
                    <div class="mt-1 mb-1 ml-2">
                        <span class="text-red-500 font-bold">✗</span>
                        <span class="ml-1 text-red-500">Storage directory not found: %s</span>
                    </div>
                    HTML,
                $storagePath,
            ));

            return Command::FAILURE;
        }

        $certs = $this->loadCerts($dir, $storagePath);

        if (empty($certs)) {
            render(sprintf(
                <<<HTML
                    <div class="mt-1 mb-1 ml-2">
                        <span class="text-yellow-500 font-bold">–</span>
                        <span class="ml-1">No certificates found in %s</span>
                    </div>
                    HTML,
                $storagePath,
            ));

            return Command::SUCCESS;
        }

        $this->renderTable($certs, $storagePath);

        return Command::SUCCESS;
    }

    /**
     * @return list<array{cert: StoredCertificate, keyType: KeyType}>
     */
    private function loadCerts(string $dir, string $storagePath): array
    {
        $keyTypeValues = implode('|', array_map(fn(KeyType $k) => preg_quote($k->value, '/'), KeyType::cases()));
        $pattern       = '/^.+\.(' . $keyTypeValues . ')\.cert\.json$/';

        $files = glob($dir . '*.cert.json') ?: [];
        $fs    = new FilesystemStorage($storagePath);
        $certs = [];

        foreach ($files as $file) {
            $base = basename($file);

            if (!preg_match($pattern, $base, $m)) {
                continue;
            }

            $keyType = KeyType::from($m[1]);
            // Strip the trailing .{keyType}.cert.json to recover the safe domain slug
            $safeDomain = substr($base, 0, -(strlen('.' . $keyType->value . '.cert.json')));
            $cert       = $fs->getCertificate($safeDomain, $keyType);

            if ($cert !== null) {
                $certs[] = ['cert' => $cert, 'keyType' => $keyType];
            }
        }

        usort($certs, fn($a, $b) => $a['cert']->expiresAt <=> $b['cert']->expiresAt);

        return $certs;
    }

    /**
     * @param list<array{cert: StoredCertificate, keyType: KeyType}> $certs
     */
    private function renderTable(array $certs, string $storagePath): void
    {
        $rows = '';

        foreach ($certs as ['cert' => $cert, 'keyType' => $keyType]) {
            $days    = $cert->remainingDays();
            $expired = $cert->isExpired();

            [$statusIcon, $statusText, $statusColor] = match (true) {
                $expired    => ['✗', 'Expired', 'text-red-500'],
                $days <= 7  => ['!', 'Expiring soon', 'text-red-500'],
                $days <= 30 => ['!', 'Renewal due', 'text-yellow-500'],
                default     => ['✓', 'Valid', 'text-green-500'],
            };

            $daysColor = $expired || $days <= 7 ? 'text-red-500' : ($days <= 30 ? 'text-yellow-500' : 'text-green-400');
            $keyLabel  = match ($keyType) {
                KeyType::EC_P256  => 'EC P-256',
                KeyType::EC_P384  => 'EC P-384',
                KeyType::RSA_2048 => 'RSA 2048',
                KeyType::RSA_4096 => 'RSA 4096',
            };

            $domains    = implode(', ', $cert->domains);
            $expiresStr = $cert->expiresAt->format('M j, Y');

            $rows .= sprintf(
                <<<HTML
                    <tr>
                        <td class="pr-4"><span class="%s font-bold">%s</span></td>
                        <td class="pr-4">%s</td>
                        <td class="pr-4 text-gray-500">%s</td>
                        <td class="pr-4 %s">%s</td>
                        <td class="%s">%s</td>
                    </tr>
                    HTML,
                $statusColor,
                $statusIcon,
                htmlspecialchars($domains),
                $keyLabel,
                $statusColor,
                $statusText,
                $daysColor,
                $expiresStr,
            );
        }

        $count = count($certs);

        render(sprintf(
            <<<HTML
                <div class="mt-1 mb-1">
                    <div class="ml-2 text-gray-500">%d certificate%s in %s</div>
                    <table class="mt-1 ml-4">
                        %s
                    </table>
                </div>
                HTML,
            $count,
            $count === 1 ? '' : 's',
            $storagePath,
            $rows,
        ));
    }
}
