<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Command;

use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class MigrateToRedirectsCommand extends Command
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Migrates tinyurls to the core redirects extension.')
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'The storage PID of the tinyurls to migrate.',
            )
            ->addOption(
                'target-pid',
                null,
                InputOption::VALUE_REQUIRED,
                'The storage PID for the created redirect records.',
                '0',
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'The hostname to use for the redirects, use "*" as wildcard for all hosts.',
                '*',
            )
            ->addOption(
                'url-template',
                null,
                InputOption::VALUE_REQUIRED,
                'The URL template to use for the source path. Use ###TINY_URL_KEY### as placeholder. '
                . 'Example: /s/###TINY_URL_KEY###. Defaults to a path derived from the configured '
                . 'speakingUrlTemplate extension setting, if speaking URLs are enabled and configured. '
                . 'Required otherwise.',
                $this->buildDefaultUrlTemplate(),
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If set, no changes will be made to the database.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pid = $input->getOption('pid');
        $targetPid = (int)$input->getOption('target-pid');
        $host = $input->getOption('host');
        $urlTemplate = $input->getOption('url-template');
        $dryRun = (bool)$input->getOption('dry-run');

        if ($pid === null) {
            $io->error('The --pid option is required.');
            return Command::FAILURE;
        }

        if ($urlTemplate === null) {
            $io->error(
                'The --url-template option is required because speaking URLs are not enabled, or no '
                . 'speakingUrlTemplate with a ###TINY_URL_KEY### placeholder is configured.',
            );
            return Command::FAILURE;
        }

        $countQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_tinyurls_urls');
        $countQueryBuilder->getRestrictions()->removeAll();
        $countQueryBuilder->count('uid')
            ->from('tx_tinyurls_urls')
            ->where($countQueryBuilder->expr()->eq('pid', $countQueryBuilder->createNamedParameter((int)$pid)));
        $totalCount = (int)$countQueryBuilder->executeQuery()->fetchOne();

        if ($totalCount === 0) {
            $io->note(sprintf('No tinyurls found for PID %d.', $pid));
            return Command::SUCCESS;
        }

        $io->progressStart($totalCount);

        $rowsQueryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_tinyurls_urls');
        $rowsQueryBuilder->getRestrictions()->removeAll();
        $rows = $rowsQueryBuilder->select('*')
            ->from('tx_tinyurls_urls')
            ->where($rowsQueryBuilder->expr()->eq('pid', $rowsQueryBuilder->createNamedParameter((int)$pid)))
            ->executeQuery()
            ->iterateAssociative();

        $stats = [
            'success' => 0,
            'skipped' => 0,
            'alreadyMigrated' => 0,
            'errors' => 0,
        ];
        $now = time();
        $chunk = [];

        foreach ($rows as $record) {
            $chunk[] = $record;

            if (count($chunk) >= self::CHUNK_SIZE) {
                $this->migrateChunk($chunk, $host, $urlTemplate, $targetPid, $dryRun, $now, $io, $stats);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->migrateChunk($chunk, $host, $urlTemplate, $targetPid, $dryRun, $now, $io, $stats);
        }

        $io->progressFinish();

        $message = sprintf(
            '%d records migrated, %d skipped (one-time use), %d already migrated, %d errors.',
            $stats['success'],
            $stats['skipped'],
            $stats['alreadyMigrated'],
            $stats['errors'],
        );

        if ($dryRun) {
            $io->success('Dry run completed. ' . $message);
        } else {
            $io->success('Migration completed. ' . $message);
        }

        return Command::SUCCESS;
    }

    /**
     * Derives a default source path template from the configured speakingUrlTemplate,
     * stripping a leading host placeholder (e.g. ###TYPO3_SITE_URL###) since the redirect
     * source_path must not contain the host.
     *
     * Returns null if speaking URLs are not enabled, or no usable speakingUrlTemplate is
     * configured, so that --url-template becomes a required option instead of silently
     * falling back to a guessed path that may not match anything.
     */
    private function buildDefaultUrlTemplate(): ?string
    {
        if (!$this->extensionConfiguration->areSpeakingUrlsEnabled()) {
            return null;
        }

        $speakingUrlTemplate = $this->extensionConfiguration->getSpeakingUrlTemplate();

        if (!str_contains($speakingUrlTemplate, '###TINY_URL_KEY###')) {
            return null;
        }

        $path = preg_replace('/^###[A-Z0-9_]+###/', '', $speakingUrlTemplate, 1) ?? $speakingUrlTemplate;

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    private function findExistingSourcePaths(string $host, array $sourcePaths): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder->select('source_path')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter($host)),
                $queryBuilder->expr()->in(
                    'source_path',
                    $queryBuilder->createNamedParameter($sourcePaths, Connection::PARAM_STR_ARRAY),
                ),
            )
            ->executeQuery()
            ->fetchFirstColumn();

        return array_flip($rows);
    }

    private function migrateChunk(
        array $chunk,
        string $host,
        string $urlTemplate,
        int $targetPid,
        bool $dryRun,
        int $now,
        SymfonyStyle $io,
        array &$stats,
    ): void {
        $eligibleRecords = [];
        $sourcePaths = [];

        foreach ($chunk as $record) {
            if ((bool)$record['delete_on_use']) {
                $stats['skipped']++;
                $io->progressAdvance();
                continue;
            }

            $sourcePath = str_replace('###TINY_URL_KEY###', $record['urlkey'], $urlTemplate);
            $record['sourcePath'] = $sourcePath;
            $eligibleRecords[] = $record;
            $sourcePaths[] = $sourcePath;
        }

        if ($eligibleRecords === []) {
            return;
        }

        $existingSourcePaths = $this->findExistingSourcePaths($host, $sourcePaths);
        $redirectConnection = $this->connectionPool->getConnectionForTable('sys_redirect');

        foreach ($eligibleRecords as $record) {
            if (isset($existingSourcePaths[$record['sourcePath']])) {
                $stats['alreadyMigrated']++;
                $io->progressAdvance();
                continue;
            }

            $redirectData = [
                'pid' => $targetPid,
                'createdon' => $now,
                'updatedon' => (int)$record['tstamp'],
                'source_host' => $host,
                'source_path' => $record['sourcePath'],
                'target' => $record['target_url'],
                'target_statuscode' => 301,
                'hitcount' => (int)$record['counter'],
                'endtime' => (int)$record['valid_until'],
                'description' => $record['comment'] ?? '',
            ];

            try {
                if (!$dryRun) {
                    $redirectConnection->insert('sys_redirect', $redirectData);
                }
                $stats['success']++;
            } catch (DBALException $e) {
                $io->error(sprintf('Error migrating record %d: %s', $record['uid'] ?? 0, $e->getMessage()));
                $stats['errors']++;
            }

            $io->progressAdvance();
        }
    }
}
