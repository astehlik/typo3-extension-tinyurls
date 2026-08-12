<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Tx\Tinyurls\Command\MigrateToRedirectsCommand;
use Tx\Tinyurls\Configuration\ConfigKeys;
use Tx\Tinyurls\Tests\Functional\AbstractFunctionalTestCase;

class MigrateToRedirectsCommandTest extends AbstractFunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['typo3/cms-redirects'];

    public function testDefaultUrlTemplateIsDerivedFromExtensionConfiguration(): void
    {
        $this->importDefaultFixture();
        $this->enableSpeakingUrls();
        $tester = $this->getCommandTester();

        // No --url-template given, the default is derived from the speakingUrlTemplate
        // extension setting (###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY### by default).
        $tester->execute(['--pid' => '2']);

        $redirects = $this->getAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertSame('/tinyurl/oth999', $redirects[0]['source_path']);
    }

    public function testDryRunDoesNotWriteToDatabase(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        $tester->execute(['--pid' => '1', '--url-template' => '/###TINY_URL_KEY###', '--dry-run' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertDisplayContains('Dry run completed.', $tester);
        $this->assertDisplayContains('2 records migrated', $tester);
        $this->assertSame([], $this->getAllRedirects());
    }

    public function testFailsWithoutPidOption(): void
    {
        $tester = $this->getCommandTester();

        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertDisplayContains('--pid option is required', $tester);
    }

    public function testFailsWithoutUrlTemplateWhenNotDerivable(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        // Speaking URLs are disabled by default in the test instance, so there is no
        // sensible default source path template to fall back to.
        $tester->execute(['--pid' => '1']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertDisplayContains('--url-template option is required', $tester);
        $this->assertSame([], $this->getAllRedirects());
    }

    public function testMigratesExpiredRecordsWithPastEndtime(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        $tester->execute(['--pid' => '1', '--url-template' => '/###TINY_URL_KEY###']);

        $redirect = $this->getRedirectBySourcePath('/exp456');
        $this->assertNotNull($redirect);
        $this->assertSame('https://example.com/expired', $redirect['target']);
        $this->assertSame(1000000000, (int)$redirect['endtime']);
    }

    public function testMigratesLargeAmountOfRecordsAcrossChunkBoundary(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/tinyurl_migration_large.csv');
        $tester = $this->getCommandTester();

        $tester->execute(['--pid' => '1', '--url-template' => '/###TINY_URL_KEY###']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertDisplayContains('620 records migrated', $tester);
        $this->assertCount(620, $this->getAllRedirects());

        // Re-running must not create duplicates, even across the chunk boundary.
        $tester->execute(['--pid' => '1', '--url-template' => '/###TINY_URL_KEY###']);
        $this->assertDisplayContains('620 already migrated', $tester);
        $this->assertCount(620, $this->getAllRedirects());
    }

    public function testMigratesOnlyEligibleRecordsForGivenStoragePid(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        $tester->execute(['--pid' => '1', '--url-template' => '/###TINY_URL_KEY###']);

        $this->assertSame(0, $tester->getStatusCode());

        // "Normal URL" and "Expired URL" are migrated, "One time URL" (delete_on_use) is not.
        $this->assertCount(2, $this->getAllRedirects());

        $redirect = $this->getRedirectBySourcePath('/abc123');
        $this->assertNotNull($redirect);
        $this->assertSame('*', $redirect['source_host']);
        $this->assertSame('https://example.com/normal', $redirect['target']);
        $this->assertSame(301, (int)$redirect['target_statuscode']);
        $this->assertSame(5, (int)$redirect['hitcount']);
        $this->assertSame('Normal URL', $redirect['description']);
        $this->assertSame(0, (int)$redirect['pid']);
        $this->assertSame(1600000000, (int)$redirect['updatedon']);
    }

    public function testReturnsSuccessAndNoteForUnknownPid(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        $tester->execute(['--pid' => '999', '--url-template' => '/###TINY_URL_KEY###']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertDisplayContains('No tinyurls found', $tester);
        $this->assertSame([], $this->getAllRedirects());
    }

    public function testSkipsAlreadyMigratedRecordsOnSecondRun(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        $tester->execute(['--pid' => '1', '--url-template' => '/###TINY_URL_KEY###']);
        $tester->execute(['--pid' => '1', '--url-template' => '/###TINY_URL_KEY###']);

        $this->assertCount(2, $this->getAllRedirects());
        $this->assertDisplayContains('2 already migrated', $tester);
    }

    public function testSkipsRecordsFromOtherStoragePid(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        $tester->execute(['--pid' => '2', '--url-template' => '/###TINY_URL_KEY###']);

        $redirects = $this->getAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertSame('/oth999', $redirects[0]['source_path']);
    }

    public function testUsesCustomHostUrlTemplateAndTargetPid(): void
    {
        $this->importDefaultFixture();
        $tester = $this->getCommandTester();

        $tester->execute([
            '--pid' => '1',
            '--host' => 'example.org',
            '--url-template' => '/s/###TINY_URL_KEY###',
            '--target-pid' => '5',
        ]);

        $redirect = $this->getRedirectBySourcePath('/s/abc123');
        $this->assertNotNull($redirect);
        $this->assertSame('example.org', $redirect['source_host']);
        $this->assertSame(5, (int)$redirect['pid']);
    }

    /**
     * SymfonyStyle word-wraps block text (success/error/note) to the terminal width, which
     * is narrower and unset in CI (no TTY) than locally, so long summary sentences can wrap
     * mid-phrase. Collapsing whitespace makes the assertion independent of that wrapping.
     */
    private function assertDisplayContains(string $needle, CommandTester $tester): void
    {
        $normalizedDisplay = preg_replace('/\\s+/', ' ', $tester->getDisplay());
        $this->assertStringContainsString($needle, $normalizedDisplay);
    }

    private function enableSpeakingUrls(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls'][ConfigKeys::CREATE_SPEAKING_URLS] = '1';
    }

    private function getAllRedirects(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder->select('*')
            ->from('sys_redirect')
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function getCommandTester(): CommandTester
    {
        return new CommandTester($this->get(MigrateToRedirectsCommand::class));
    }

    private function getRedirectBySourcePath(string $sourcePath): ?array
    {
        foreach ($this->getAllRedirects() as $redirect) {
            if ($redirect['source_path'] === $sourcePath) {
                return $redirect;
            }
        }

        return null;
    }

    private function importDefaultFixture(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/tinyurl_migration.csv');
    }
}
