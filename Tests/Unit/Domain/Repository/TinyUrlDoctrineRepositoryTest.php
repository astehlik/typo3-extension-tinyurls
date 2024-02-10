<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Database\StoragePageQueryRestriction;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlDoctrineRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Domain\Validator\TinyUrlValidator;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Exception\TinyUrlValidationException;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Extbase\Error\Result;
use Closure;
use InvalidArgumentException;

/**
 * @backupGlobals enabled
 */
class TinyUrlDoctrineRepositoryTest extends TestCase
{
    private Connection|MockObject $databaseConnectionMock;

    private MockObject|QueryBuilder $databaseQueryBuilderMock;

    private MockObject|QueryRestrictionContainerInterface $databaseQueryRestrictionsContainerMock;

    private TinyUrlDoctrineRepository $doctrineRepository;

    private ExtensionConfiguration|MockObject $extensionConfiugrationMock;

    private MockObject|TinyUrlValidator $tinyUrlValidatorMock;

    private MockObject|UrlUtils $urlUtilsMock;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls'] = [];

        $this->databaseConnectionMock = $this->createMock(Connection::class);
        $databaseConnectionPoolMock = $this->createMock(ConnectionPool::class);
        $this->databaseQueryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->databaseQueryRestrictionsContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $this->extensionConfiugrationMock = $this->createMock(ExtensionConfiguration::class);

        $this->databaseQueryBuilderMock->expects(self::any())
            ->method(
                new Callback(
                    static function (string $methodName) {
                        return in_array(
                            $methodName,
                            [
                                'delete',
                                'from',
                                'select',
                                'set',
                                'update',
                                'where',
                            ],
                            true
                        );
                    }
                )
            )
            ->willReturnSelf();
        $this->databaseQueryBuilderMock->method('getRestrictions')
            ->willReturn($this->databaseQueryRestrictionsContainerMock);

        $databaseConnectionPoolMock->method('getQueryBuilderForTable')
            ->willReturn($this->databaseQueryBuilderMock);
        $databaseConnectionPoolMock->method('getConnectionForTable')
            ->willReturn($this->databaseConnectionMock);

        $this->urlUtilsMock = $this->createMock(UrlUtils::class);

        $this->doctrineRepository = new TinyUrlDoctrineRepository(
            $databaseConnectionPoolMock,
            $this->extensionConfiugrationMock,
            $this->urlUtilsMock
        );

        $this->tinyUrlValidatorMock = $this->createMock(TinyUrlValidator::class);
        $this->doctrineRepository->overrideTinyUrlValidator($this->tinyUrlValidatorMock);
    }

    public function testCountTinyUrlHitIncreasesCountByOne(): void
    {
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $resultMock->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn($this->getDummyDatabaseRow());

        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('set')
            ->with('counter', '1');

        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($resultMock);

        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeStatement');

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3342);
        $this->doctrineRepository->countTinyUrlHit($tinyUrl);
    }

    public function testFindTinyUrlByKeyReturnsTinyUrlWithFoundData(): void
    {
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $resultMock->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByKey('the key to find');
        self::assertSame(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByKeyThrowsNotFoundExceptionForEmptyResult(): void
    {
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $resultMock->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByKey('the key to find');
    }

    public function testFindTinyUrlByTargetUrlReturnsTinyUrlWithFoundData(): void
    {
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $resultMock->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
        self::assertSame(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByTargetUrlThrowsNotFoundExceptionForEmptyResult(): void
    {
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $resultMock->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
    }

    public function testFindTinyUrlByUidReturnsTinyUrlWithFoundData(): void
    {
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $resultMock->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByUid(945);
        self::assertSame(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByUidThrowsNotFoundExceptionForEmptyResult(): void
    {
        $resultMock = $this->createMock(\Doctrine\DBAL\Result::class);
        $resultMock->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByUid(293);
    }

    public function testGetQueryBuilderClearsAllRestrictions(): void
    {
        $this->databaseQueryRestrictionsContainerMock->expects(self::once())
            ->method('removeAll');
        $this->doctrineRepository->deleteTinyUrlByKey('key');
    }

    public function testGetQueryInitializesStoragePageRestriction(): void
    {
        $this->databaseQueryRestrictionsContainerMock->expects(self::once())
            ->method('add')
            ->with(self::isInstanceOf(StoragePageQueryRestriction::class));
        $this->doctrineRepository->deleteTinyUrlByKey('key');
    }

    public function testGetQueryInitializesStoragePageRestrictionWithConfiguredStoragePage(): void
    {
        $this->extensionConfiugrationMock->method('getUrlRecordStoragePid')
            ->willReturn(389484);
        $this->databaseQueryRestrictionsContainerMock->expects(self::once())
            ->method('add')
            ->with(
                self::callback(
                    static function (StoragePageQueryRestriction $queryRestriction) {
                        return $queryRestriction->getStoragePageUid() === 389484;
                    }
                )
            );
        $this->doctrineRepository->deleteTinyUrlByKey('key');
    }

    public function testInsertNewTinyUrlDoesNotOverwriteCustomUrlKey(): void
    {
        $this->initializeTinyUrlValidatorMock();

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('customkey');

        $this->prepareInsertQuery(2323);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
        self::assertSame('customkey', $tinyUrl->getUrlkey());
    }

    public function testInsertNewTinyUrlPostProcessesTinyUrl(): void
    {
        $tinyUrlUid = 4845;

        $this->prepareInsertQuery($tinyUrlUid);

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('custom-key');
        $tinyUrl->setTargetUrl('http://the-target-url.tld');
        self::assertTrue($tinyUrl->getTargetUrlHasChanged());

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);

        self::assertSame($tinyUrlUid, $tinyUrl->getUid());
        self::assertFalse($tinyUrl->getTargetUrlHasChanged());
        self::assertFalse($tinyUrl->hasCustomUrlKey());
    }

    public function testInsertNewTinyUrlPreProcessesTinyUrl(): void
    {
        /** @var MockObject|TinyUrl $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->expects(self::once())->method('persistPreProcess');

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlRegeneratesUrlKey(): void
    {
        $this->initializeTinyUrlValidatorMock();

        $tinyUrl = TinyUrl::createNew();

        $this->initializeUrlKeyGeneratorMock($tinyUrl);

        $this->prepareInsertQuery(2323);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
        self::assertSame('the-generated-key', $tinyUrl->getUrlkey());
    }

    public function testInsertNewTinyUrlSetsStoragePid(): void
    {
        $this->extensionConfiugrationMock->expects(self::once())
            ->method('getUrlRecordStoragePid')
            ->willReturn(58923);

        $tinyUrl = TinyUrl::createNew();
        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);

        self::assertSame(58923, $tinyUrl->getPid());
    }

    public function testInsertNewTinyUrlThrowsValidationExceptionForInvalidData(): void
    {
        $this->expectException(TinyUrlValidationException::class);

        $this->initializeTinyUrlValidatorMock(true);

        $tinyUrl = TinyUrl::createNew();

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlUpdatesGeneratedUrlKeyInDatabase(): void
    {
        $tinyUrl = TinyUrl::createNew();

        $this->initializeUrlKeyGeneratorMock($tinyUrl);

        $this->prepareInsertQuery(2323);

        $this->databaseConnectionMock->expects(self::once())
            ->method('update')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                self::callback(
                    static function (array $databaseRow) {
                        return $databaseRow['urlkey'] === 'the-generated-key';
                    }
                )
            );

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlUsesNewTinyUrlUid(): void
    {
        $tinyUrl = TinyUrl::createNew();

        $this->prepareInsertQuery(4894949);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
        self::assertSame(4894949, $tinyUrl->getUid());
    }

    public function testPurgeInvalidUrlsExecutesDeleteQuery(): void
    {
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('delete');
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('executeStatement');
        $this->doctrineRepository->purgeInvalidUrls();
    }

    public function testTransactionalUsesTransactionalOfDatabaseConnection(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $this->databaseConnectionMock->expects(self::once())
            ->method('transactional');
        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUpdatesDatabase(): void
    {
        $tinyUrlData = $this->getDummyDatabaseRow();
        $tinyUrl = TinyUrl::createFromDatabaseRow($tinyUrlData);

        unset($tinyUrlData['uid'], $tinyUrlData['tstamp']);

        $this->databaseConnectionMock->expects(self::once())
            ->method('update')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                self::callback(static fn(array $databaseRow) => array_diff_assoc($tinyUrlData, $databaseRow) === []),
                ['uid' => 945]
            );

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlPostProcessesTinyUrl(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(34543);
        $tinyUrl->setTargetUrl('http://the-target-url.tld');
        $tinyUrl->setCustomUrlKey('the-custom-key');

        self::assertTrue($tinyUrl->getTargetUrlHasChanged());
        self::assertTrue($tinyUrl->hasCustomUrlKey());

        $this->doctrineRepository->updateTinyUrl($tinyUrl);

        self::assertFalse($tinyUrl->getTargetUrlHasChanged());
        self::assertFalse($tinyUrl->hasCustomUrlKey());
    }

    public function testUpdateTinyUrlPreProcessesTinyUrl(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPreProcess();

        $tinyUrl->persistPostProcessInsert(34435);
        $tinyUrl->setCustomUrlKey('custom-key');
        $tstampOriginal = $tinyUrl->getTstamp();

        $this->doctrineRepository->updateTinyUrl($tinyUrl);

        self::assertSame('custom-key', $tinyUrl->getUrlkey());
        self::assertNotSame($tstampOriginal, $tinyUrl->getTstamp());
    }

    public function testUpdateTinyUrlThrowsExceptionForNewTinyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only existing TinyUrl records can be updated.');
        $tinyUrl = TinyUrl::createNew();
        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlThrowsValidationExceptionForInvalidTinyUrlData(): void
    {
        $this->expectException(TinyUrlValidationException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(238);

        $this->initializeTinyUrlValidatorMock(true);

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    protected function getDummyDatabaseRow(): array
    {
        return [
            'uid' => 945,
            'pid' => 48,
            'tstamp' => 1488717824,
            'counter' => 4323237,
            'comment' => 'The cool comment',
            'urlkey' => 'theurlkey',
            'target_url' => 'http://the-target.url',
            'target_url_hash' => '59d24fcbfb6c586daeabf73ddd58cd01affb56d4',
            'delete_on_use' => 0,
            'valid_until' => time() + 60000,
        ];
    }

    protected function initializeTinyUrlValidatorMock(bool $hasErrors = false): void
    {
        $result = $this->createMock(Result::class);

        $result->method('hasErrors')->willReturn($hasErrors);

        $this->tinyUrlValidatorMock->method('validate')->willReturn($result);
    }

    protected function prepareInsertQuery(int $newUid): void
    {
        $this->databaseConnectionMock->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(
                static function (Closure $callback): void {
                    $callback();
                }
            );

        $this->databaseConnectionMock->expects(self::once())
            ->method('lastInsertId')
            ->with(TinyUrlRepository::TABLE_URLS, 'uid')
            ->willReturn((string)$newUid);
    }

    private function initializeUrlKeyGeneratorMock(TinyUrl $tinyUrl): void
    {
        $this->urlUtilsMock->expects(self::once())
            ->method('regenerateUrlKey')
            ->with($tinyUrl)
            ->willReturnCallback(
                static function (TinyUrl $tinyUrl): void {
                    $tinyUrl->setGeneratedUrlKey('the-generated-key');
                }
            );
    }
}
