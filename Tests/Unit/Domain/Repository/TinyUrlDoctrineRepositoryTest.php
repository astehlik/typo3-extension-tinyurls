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

use DMS\PHPUnitExtensions\ArraySubset\Constraint\ArraySubset;
use Doctrine\DBAL\Driver\Statement;
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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Extbase\Error\Result;

/**
 * @backupGlobals enabled
 */
class TinyUrlDoctrineRepositoryTest extends TestCase
{
    /**
     * @var Connection|MockObject
     */
    protected $databaseConnectionMock;

    /**
     * @var ConnectionPool|MockObject
     */
    protected $databaseConnectionPoolMock;

    /**
     * @var MockObject|QueryBuilder
     */
    protected $databaseQueryBuilderMock;

    /**
     * @var MockObject|QueryRestrictionContainerInterface
     */
    protected $databaseQueryRestrictionsContainerMock;

    /**
     * @var TinyUrlDoctrineRepository
     */
    protected $doctrineRepository;

    /**
     * @var ExtensionConfiguration|MockObject
     */
    protected $extensionConfiugrationMock;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls'] = [];

        $this->databaseConnectionMock = $this->createMock(Connection::class);
        $this->databaseConnectionPoolMock = $this->createMock(ConnectionPool::class);
        $this->databaseQueryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->databaseQueryRestrictionsContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $this->extensionConfiugrationMock = $this->createMock(ExtensionConfiguration::class);

        $this->databaseQueryBuilderMock->expects(self::any())
            ->method(
                new Callback(
                    function (string $methodName) {
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

        $this->databaseConnectionPoolMock->method('getQueryBuilderForTable')
            ->willReturn($this->databaseQueryBuilderMock);
        $this->databaseConnectionPoolMock->method('getConnectionForTable')
            ->willReturn($this->databaseConnectionMock);

        $this->doctrineRepository = new TinyUrlDoctrineRepository();
        $this->doctrineRepository->setDatabaseConnectionPool($this->databaseConnectionPoolMock);
        $this->doctrineRepository->injectExtensionConfiguration($this->extensionConfiugrationMock);
    }

    public function testCountTinyUrlHitIncreasesCountByOne(): void
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects(self::once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());

        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('set')
            ->with('counter', '1');

        $this->databaseQueryBuilderMock->expects(self::exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                null,
                $resultMock
            );

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3342);
        $this->doctrineRepository->countTinyUrlHit($tinyUrl);
    }

    public function testFindTinyUrlByKeyReturnsTinyUrlWithFoundData(): void
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects(self::once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('execute')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByKey('the key to find');
        self::assertSame(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByKeyThrowsNotFoundExceptionForEmptyResult(): void
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects(self::once())
            ->method('fetch')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('execute')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByKey('the key to find');
    }

    public function testFindTinyUrlByTargetUrlReturnsTinyUrlWithFoundData(): void
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects(self::once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('execute')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
        self::assertSame(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByTargetUrlThrowsNotFoundExceptionForEmptyResult(): void
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects(self::once())
            ->method('fetch')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('execute')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
    }

    public function testFindTinyUrlByUidReturnsTinyUrlWithFoundData(): void
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects(self::once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('execute')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByUid(945);
        self::assertSame(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByUidThrowsNotFoundExceptionForEmptyResult(): void
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects(self::once())
            ->method('fetch')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects(self::once())
            ->method('execute')
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
                    function (StoragePageQueryRestriction $queryRestriction) {
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
        $this->prepareInsertQuery(4845);

        /** @var MockObject|TinyUrl $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->expects(self::once())->method('persistPostProcessInsert')->with(4845);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
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

        $this->prepareInsertQuery(2323);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
        self::assertMatchesRegularExpression('/LD\-[a-z0-9]+/', $tinyUrl->getUrlkey());
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

        $tinyUrl = TinyUrl::createNew();
        $validUntil = new \DateTime();
        $validUntil->modify('-1 day');
        $tinyUrl->setValidUntil($validUntil);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlUpdatesGeneratedUrlKeyInDatabase(): void
    {
        $tinyUrl = TinyUrl::createNew();

        $this->prepareInsertQuery(2323);

        $this->databaseConnectionMock->expects(self::once())
            ->method('update')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                self::callback(
                    function (array $databaseRow) {
                        return preg_match('/LD-[0-9a-z]+/', $databaseRow['urlkey']) === 1;
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
            ->method('execute');
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
                new ArraySubset($tinyUrlData),
                ['uid' => 945]
            );

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlPostProcessesTinyUrl(): void
    {
        /** @var MockObject|TinyUrl $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->method('isNew')->willReturn(false);
        $tinyUrl->expects(self::once())->method('persistPostProcess');

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlPreProcessesTinyUrl(): void
    {
        /** @var MockObject|TinyUrl $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->method('isNew')->willReturn(false);
        $tinyUrl->expects(self::once())->method('persistPreProcess');

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlThrowsExceptionForNewTinyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only existing TinyUrl records can be updated.');
        $tinyUrl = TinyUrl::createNew();
        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlThrowsValidationExceptionForInvalidTinyUrlData(): void
    {
        $this->expectException(TinyUrlValidationException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(238);

        $validUntil = new \DateTime();
        $validUntil->modify('-1 day');
        $tinyUrl->setValidUntil($validUntil);

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

    protected function initializeTinyUrlValidatorMock(): void
    {
        $result = $this->createMock(Result::class);

        /** @var MockObject|TinyUrlValidator $validator */
        $validator = $this->createMock(TinyUrlValidator::class);
        $validator->method('validate')->willReturn($result);
        $this->doctrineRepository->setTinyUrlValidator($validator);
    }

    protected function prepareInsertQuery(int $newUid): void
    {
        $this->databaseConnectionMock->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(
                function (\Closure $callback): void {
                    $callback();
                }
            );

        $this->databaseConnectionMock->expects(self::once())
            ->method('lastInsertId')
            ->with(TinyUrlRepository::TABLE_URLS, 'uid')
            ->willReturn((string)$newUid);
    }
}
