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

use Closure;
use DateTime;
use DMS\PHPUnitExtensions\ArraySubset\Constraint\ArraySubset;
use Doctrine\DBAL\Driver\Statement;
use InvalidArgumentException;
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
     * @var QueryBuilder|MockObject
     */
    protected $databaseQueryBuilderMock;

    /**
     * @var QueryRestrictionContainerInterface|MockObject
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

        $this->databaseQueryBuilderMock->expects($this->any())
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
                            ]
                        );
                    }
                )
            )
            ->will($this->returnSelf());
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

    public function testCountTinyUrlHitIncreasesCountByOne()
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects($this->once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());

        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('set')
            ->with('counter', '1');

        $this->databaseQueryBuilderMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                null,
                $resultMock
            );

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3342);
        $this->doctrineRepository->countTinyUrlHit($tinyUrl);
    }

    public function testFindTinyUrlByKeyReturnsTinyUrlWithFoundData()
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects($this->once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('execute')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByKey('the key to find');
        $this->assertEquals(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByKeyThrowsNotFoundExceptionForEmptyResult()
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('execute')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByKey('the key to find');
    }

    public function testFindTinyUrlByTargetUrlReturnsTinyUrlWithFoundData()
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects($this->once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('execute')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
        $this->assertEquals(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByTargetUrlThrowsNotFoundExceptionForEmptyResult()
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('execute')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
    }

    public function testFindTinyUrlByUidReturnsTinyUrlWithFoundData()
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects($this->once())
            ->method('fetch')
            ->willReturn($this->getDummyDatabaseRow());
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('execute')
            ->willReturn($resultMock);
        $tinyUrl = $this->doctrineRepository->findTinyUrlByUid(945);
        $this->assertEquals(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByUidThrowsNotFoundExceptionForEmptyResult()
    {
        $resultMock = $this->createMock(Statement::class);
        $resultMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('execute')
            ->willReturn($resultMock);
        $this->expectException(TinyUrlNotFoundException::class);
        $this->doctrineRepository->findTinyUrlByUid(293);
    }

    public function testGetQueryBuilderClearsAllRestrictions()
    {
        $this->databaseQueryRestrictionsContainerMock->expects($this->once())
            ->method('removeAll');
        $this->doctrineRepository->deleteTinyUrlByKey('key');
    }

    public function testGetQueryInitializesStoragePageRestriction()
    {
        $this->databaseQueryRestrictionsContainerMock->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(StoragePageQueryRestriction::class));
        $this->doctrineRepository->deleteTinyUrlByKey('key');
    }

    public function testGetQueryInitializesStoragePageRestrictionWithConfiguredStoragePage()
    {
        $this->extensionConfiugrationMock->method('getUrlRecordStoragePid')
            ->willReturn(389484);
        $this->databaseQueryRestrictionsContainerMock->expects($this->once())
            ->method('add')
            ->with(
                $this->callback(
                    function (StoragePageQueryRestriction $queryRestriction) {
                        return 389484 === $queryRestriction->getStoragePageUid();
                    }
                )
            );
        $this->doctrineRepository->deleteTinyUrlByKey('key');
    }

    public function testInsertNewTinyUrlDoesNotOverwriteCustomUrlKey()
    {
        $this->initializeTinyUrlValidatorMock();

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('customkey');

        $this->prepareInsertQuery(2323);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
        $this->assertEquals('customkey', $tinyUrl->getUrlkey());
    }

    public function testInsertNewTinyUrlPostProcessesTinyUrl()
    {
        $this->prepareInsertQuery(4845);

        /** @var TinyUrl|MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->expects($this->once())->method('persistPostProcessInsert')->with(4845);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlPreProcessesTinyUrl()
    {
        /** @var TinyUrl|MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->expects($this->once())->method('persistPreProcess');

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlRegeneratesUrlKey()
    {
        $this->initializeTinyUrlValidatorMock();

        $tinyUrl = TinyUrl::createNew();

        $this->prepareInsertQuery(2323);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
        $this->assertMatchesRegularExpression('/LD\-[a-z0-9]+/', $tinyUrl->getUrlkey());
    }

    public function testInsertNewTinyUrlSetsStoragePid()
    {
        $this->extensionConfiugrationMock->expects($this->once())
            ->method('getUrlRecordStoragePid')
            ->willReturn(58923);

        $tinyUrl = TinyUrl::createNew();
        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);

        $this->assertEquals(58923, $tinyUrl->getPid());
    }

    public function testInsertNewTinyUrlThrowsValidationExceptionForInvalidData()
    {
        $this->expectException(TinyUrlValidationException::class);

        $tinyUrl = TinyUrl::createNew();
        $validUntil = new DateTime();
        $validUntil->modify('-1 day');
        $tinyUrl->setValidUntil($validUntil);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlUpdatesGeneratedUrlKeyInDatabase()
    {
        $tinyUrl = TinyUrl::createNew();

        $this->prepareInsertQuery(2323);

        $this->databaseConnectionMock->expects($this->once())
            ->method('update')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                $this->callback(
                    function (array $databaseRow) {
                        return preg_match('/LD-[0-9a-z]+/', $databaseRow['urlkey']) === 1;
                    }
                )
            );

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlUsesNewTinyUrlUid()
    {
        $tinyUrl = TinyUrl::createNew();

        $this->prepareInsertQuery(4894949);

        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
        $this->assertEquals(4894949, $tinyUrl->getUid());
    }

    public function testPurgeInvalidUrlsExecutesDeleteQuery()
    {
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('delete');
        $this->databaseQueryBuilderMock->expects($this->once())
            ->method('execute');
        $this->doctrineRepository->purgeInvalidUrls();
    }

    public function testTransactionalUsesTransactionalOfDatabaseConnection()
    {
        $tinyUrl = TinyUrl::createNew();
        $this->databaseConnectionMock->expects($this->once())
            ->method('transactional');
        $this->doctrineRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUpdatesDatabase()
    {
        $tinyUrlData = $this->getDummyDatabaseRow();
        $tinyUrl = TinyUrl::createFromDatabaseRow($tinyUrlData);

        unset($tinyUrlData['uid']);
        unset($tinyUrlData['tstamp']);

        $this->databaseConnectionMock->expects($this->once())
            ->method('update')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                new ArraySubset($tinyUrlData),
                ['uid' => 945]
            );

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlPostProcessesTinyUrl()
    {
        /** @var TinyUrl|MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->method('isNew')->willReturn(false);
        $tinyUrl->expects($this->once())->method('persistPostProcess');

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlPreProcessesTinyUrl()
    {
        /** @var TinyUrl|MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->method('isNew')->willReturn(false);
        $tinyUrl->expects($this->once())->method('persistPreProcess');

        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlThrowsExceptionForNewTinyUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only existing TinyUrl records can be updated.');
        $tinyUrl = TinyUrl::createNew();
        $this->doctrineRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlThrowsValidationExceptionForInvalidTinyUrlData()
    {
        $this->expectException(TinyUrlValidationException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(238);

        $validUntil = new DateTime();
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

    protected function initializeTinyUrlValidatorMock()
    {
        $result = $this->createMock(Result::class);
        /** @var TinyUrlValidator|MockObject $validator */
        $validator = $this->createMock(TinyUrlValidator::class);
        $validator->method('validate')->willReturn($result);
        $this->doctrineRepository->setTinyUrlValidator($validator);
    }

    protected function prepareInsertQuery(int $newUid)
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('transactional')
            ->will(
                $this->returnCallback(
                    function (Closure $callback) {
                        $callback();
                    }
                )
            );

        $this->databaseConnectionMock->expects($this->once())
            ->method('lastInsertId')
            ->with(TinyUrlRepository::TABLE_URLS, 'uid')
            ->willReturn((string)$newUid);
    }
}
