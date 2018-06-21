<?php
declare(strict_types = 1);
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

use PHPUnit\Framework\Constraint\ArraySubset;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlDatabaseRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Domain\Validator\TinyUrlValidator;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Exception\TinyUrlValidationException;
use Tx\Tinyurls\Object\ImplementationManager;
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Extbase\Error\Result;

class TinyUrlDatabaseRepositoryTest extends TestCase
{
    /**
     * @var DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $databaseConnectionMock;

    /**
     * @var TinyUrlDatabaseRepository
     */
    protected $databaseRepository;

    /**
     * @var ExtensionConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $extensionConfiugrationMock;

    /**
     * @var UrlKeyGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlKeyGeneratorMock;

    protected function setUp()
    {
        if (!class_exists('TYPO3\\CMS\\Core\\Database\\DatabaseConnection')) {
            $this->markTestSkipped('The DatabaseConnection class does not exist.');
        }

        $this->urlKeyGeneratorMock = $this->createMock(UrlKeyGenerator::class);
        ImplementationManager::getInstance()->setUrlKeyGenerator($this->urlKeyGeneratorMock);

        $this->databaseConnectionMock = $this->createMock(DatabaseConnection::class);
        $this->extensionConfiugrationMock = $this->createMock(ExtensionConfiguration::class);

        $this->databaseRepository = new TinyUrlDatabaseRepository();
        $this->databaseRepository->setDatabaseConnection($this->databaseConnectionMock);
        $this->databaseRepository->injectExtensionConfiguration($this->extensionConfiugrationMock);
    }

    protected function tearDown()
    {
        ImplementationManager::getInstance()->restoreDefaults();
    }

    public function testCountTinyUrlHitIncreasesCountByOne()
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_UPDATEquery')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                'uid=3342',
                ['counter' => '1'],
                ['counter']
            );

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3342);
        $this->databaseRepository->countTinyUrlHit($tinyUrl);
    }

    public function testDeleteTinyUrlByKeyExecutesDeleteQuery()
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('fullQuoteStr')
            ->with('the key', TinyUrlRepository::TABLE_URLS)
            ->willReturn('the quoted key');

        $this->extensionConfiugrationMock->expects($this->once())
            ->method('appendPidQuery')
            ->with('urlkey=the quoted key')
            ->willReturn('urlkey=the quoted key');

        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_DELETEquery')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                'urlkey=the quoted key'
            );
        $this->databaseRepository->deleteTinyUrlByKey('the key');
    }

    public function testFindTinyUrlByKeyQuotesUrlKey()
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('fullQuoteStr')
            ->with('the key to find', TinyUrlRepository::TABLE_URLS)
            ->willReturn('the quoted key');

        $this->extensionConfiugrationMock->expects($this->once())
            ->method('appendPidQuery')
            ->with('urlkey=the quoted key')
            ->willReturn('urlkey=the quoted key');

        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->with(
                '*',
                TinyUrlRepository::TABLE_URLS,
                'urlkey=the quoted key'
            )
            ->willReturn($this->getDummyDatabaseRow());

        $this->databaseRepository->findTinyUrlByKey('the key to find');
    }

    public function testFindTinyUrlByKeyReturnsTinyUrlWithFoundData()
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->willReturn($this->getDummyDatabaseRow());
        $tinyUrl = $this->databaseRepository->findTinyUrlByKey('the key to find');
        $this->assertEquals(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByKeyThrowsNotFoundExceptionForEmptyResult()
    {
        $this->expectException(TinyUrlNotFoundException::class);
        $this->databaseRepository->findTinyUrlByKey('the key to find');
    }

    public function testFindTinyUrlByTargetUrlReturnsTinyUrlWithFoundData()
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->willReturn($this->getDummyDatabaseRow());
        $tinyUrl = $this->databaseRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
        $this->assertEquals(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByTargetUrlThrowsNotFoundExceptionForEmptyResult()
    {
        $this->expectException(TinyUrlNotFoundException::class);
        $this->databaseRepository->findTinyUrlByTargetUrl('http://the-url-to-find.tld');
    }

    public function testFindTinyUrlByUidReturnsTinyUrlWithFoundData()
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->willReturn($this->getDummyDatabaseRow());
        $tinyUrl = $this->databaseRepository->findTinyUrlByUid(945);
        $this->assertEquals(945, $tinyUrl->getUid());
    }

    public function testFindTinyUrlByUidThrowsNotFoundExceptionForEmptyResult()
    {
        $this->expectException(TinyUrlNotFoundException::class);
        $this->databaseRepository->findTinyUrlByUid(293);
    }

    public function testInsertNewTinyUrlDoesNotOverwriteCustomUrlKey()
    {
        $this->initializeTinyUrlValidatorMock();

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('customkey');

        $this->prepareInsertQuery(2323);

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
        $this->assertEquals('customkey', $tinyUrl->getUrlkey());
    }

    public function testInsertNewTinyUrlPostProcessesTinyUrl()
    {
        $this->prepareInsertQuery(4845);

        /** @var TinyUrl|\PHPUnit_Framework_MockObject_MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->expects($this->once())->method('persistPostProcessInsert')->with(4845);

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlPreProcessesTinyUrl()
    {
        /** @var TinyUrl|\PHPUnit_Framework_MockObject_MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->expects($this->atLeastOnce())->method('persistPreProcess');

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlRegeneratesUrlKey()
    {
        $tinyUrl = TinyUrl::createNew();

        $this->urlKeyGeneratorMock->expects($this->once())
            ->method('generateTinyurlKeyForTinyUrl')
            ->with($tinyUrl)
            ->willReturn('LD-dkked92d');

        $this->initializeTinyUrlValidatorMock();

        $this->prepareInsertQuery(2323);

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
        $this->assertEquals('LD-dkked92d', $tinyUrl->getUrlkey());
    }

    public function testInsertNewTinyUrlSetsStoragePid()
    {
        $this->prepareInsertQuery(3459);

        $this->extensionConfiugrationMock->expects($this->once())
            ->method('getUrlRecordStoragePid')
            ->willReturn(58923);

        $tinyUrl = TinyUrl::createNew();
        $this->databaseRepository->insertNewTinyUrl($tinyUrl);

        $this->assertEquals(58923, $tinyUrl->getPid());
    }

    public function testInsertNewTinyUrlThrowsValidationExceptionForInvalidData()
    {
        $this->expectException(TinyUrlValidationException::class);

        $tinyUrl = TinyUrl::createNew();
        $validUntil = new \DateTime();
        $validUntil->modify('-1 day');
        $tinyUrl->setValidUntil($validUntil);

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlUpdatesGeneratedUrlKeyInDatabase()
    {
        $tinyUrl = TinyUrl::createNew();

        $this->urlKeyGeneratorMock->expects($this->once())
            ->method('generateTinyurlKeyForTinyUrl')
            ->with($tinyUrl)
            ->willReturn('LD-k2kf929');

        $this->prepareInsertQuery(2323);

        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_UPDATEquery')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                'uid=2323',
                $this->callback(
                    function (array $databaseRow) {
                        return 'LD-k2kf929' ===  $databaseRow['urlkey'];
                    }
                )
            );

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testInsertNewTinyUrlUsesNewTinyUrlUid()
    {
        $tinyUrl = TinyUrl::createNew();

        $this->prepareInsertQuery(4894949);

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
        $this->assertEquals(4894949, $tinyUrl->getUid());
    }

    public function testPurgeInvalidUrlsExecutesDeleteQuery()
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_DELETEquery');
        $this->databaseRepository->purgeInvalidUrls();
    }

    public function testTransactionalStartsTransactionAndCommitsOnSuccess()
    {
        $this->prepareInsertQuery(95959);
        $tinyUrl = TinyUrl::createNew();
        $this->databaseConnectionMock->expects($this->exactly(2))
            ->method('sql_query')
            ->withConsecutive(
                ['START TRANSACTION'],
                ['COMMIT']
            );

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testTransactionalStartsTransactionAndRollsBackOnFailure()
    {
        $this->expectException(\InvalidArgumentException::class);
        $tinyUrl = TinyUrl::createNew();
        $this->databaseConnectionMock->expects($this->exactly(2))
            ->method('sql_query')
            ->withConsecutive(
                ['START TRANSACTION'],
                ['ROLLBACK']
            );

        $this->databaseRepository->insertNewTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUpdatesDatabase()
    {
        $tinyUrlData = $this->getDummyDatabaseRow();
        $tinyUrl = TinyUrl::createFromDatabaseRow($tinyUrlData);

        unset($tinyUrlData['uid']);
        unset($tinyUrlData['tstamp']);

        $this->databaseConnectionMock->expects($this->once())
            ->method('exec_UPDATEquery')
            ->with(
                TinyUrlRepository::TABLE_URLS,
                'uid=945',
                new ArraySubset($tinyUrlData)
            );

        $this->databaseRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlPostProcessesTinyUrl()
    {
        /** @var TinyUrl|\PHPUnit_Framework_MockObject_MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->method('isNew')->willReturn(false);
        $tinyUrl->expects($this->once())->method('persistPostProcess');

        $this->databaseRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlPreProcessesTinyUrl()
    {
        /** @var TinyUrl|\PHPUnit_Framework_MockObject_MockObject $tinyUrl */
        $tinyUrl = $this->createMock(TinyUrl::class);
        $tinyUrl->method('isNew')->willReturn(false);
        $tinyUrl->expects($this->once())->method('persistPreProcess');

        $this->databaseRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlThrowsExceptionForNewTinyUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only existing TinyUrl records can be updated.');
        $tinyUrl = TinyUrl::createNew();
        $this->databaseRepository->updateTinyUrl($tinyUrl);
    }

    public function testUpdateTinyUrlThrowsValidationExceptionForInvalidTinyUrlData()
    {
        $this->expectException(TinyUrlValidationException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(238);

        $validUntil = new \DateTime();
        $validUntil->modify('-1 day');
        $tinyUrl->setValidUntil($validUntil);

        $this->databaseRepository->updateTinyUrl($tinyUrl);
    }

    protected function getDummyDatabaseRow()
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
        /** @var TinyUrlValidator|\PHPUnit_Framework_MockObject_MockObject $validator */
        $validator = $this->createMock(TinyUrlValidator::class);
        $validator->method('validate')->willReturn($result);
        $this->databaseRepository->setTinyUrlValidator($validator);
    }

    protected function prepareInsertQuery(int $newUid)
    {
        $this->databaseConnectionMock->expects($this->once())
            ->method('sql_insert_id')
            ->willReturn($newUid);
    }
}
