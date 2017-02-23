<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Unit\Hooks;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Domain\Repository\TinyUrlDatabaseRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Hooks\TceDataMap;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class TceDataMapTest extends TestCase
{
    /**
     * @var TceDataMap
     */
    protected $tceDataMapHook;

    /**
     * @var TinyUrlDatabaseRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tinyUrlRepositoryMock;

    /**
     * @var UrlUtils|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlUtilsMock;

    protected function setUp()
    {
        $this->urlUtilsMock = $this->createMock(UrlUtils::class);
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlDatabaseRepository::class);

        $this->tceDataMapHook = new TceDataMap();
        $this->tceDataMapHook->injectUrlUtils($this->urlUtilsMock);
        $this->tceDataMapHook->injectTinyUrlRepository($this->tinyUrlRepositoryMock);
    }

    public function testDoesNotRegenerateKeyForExistingUnchangedRecord()
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn(['target_url' => 'the target url', 'target_url_hash' => 'the hash']);

        $this->urlUtilsMock->expects($this->once())
            ->method('generateTinyurlHash')
            ->willReturn('the hash');

        $this->tinyUrlRepositoryMock->expects($this->never())
            ->method('updateTinyUrl');

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            '99',
            $fieldArray,
            $this->getDataHandlerMock()
        );

        $this->assertEquals($fieldArrayOriginal, $fieldArray);
    }

    public function testGeneratesHashForChangedUrl()
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn(['target_url' => 'the new target url', 'target_url_hash' => 'the old hash']);

        $this->urlUtilsMock->expects($this->once())
            ->method('generateTinyurlHash')
            ->with('the new target url')
            ->willReturn('the new hash');

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('updateTinyUrl')
            ->with(99, ['target_url_hash' => 'the new hash', 'urlkey' => null]);

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            '99',
            $fieldArray,
            $this->getDataHandlerMock()
        );

        $this->assertArraySubset(['target_url_hash' => 'the new hash'], $fieldArray);
    }

    public function testGeneratesKeyForChangedUrl()
    {
        $fieldArray = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn(['target_url' => 'the new target url', 'target_url_hash' => 'the old hash']);

        $this->urlUtilsMock->expects($this->once())
            ->method('generateTinyurlHash')
            ->willReturn('the new hash');

        $this->urlUtilsMock->expects($this->once())
            ->method('generateTinyurlKeyForUid')
            ->with(99)
            ->willReturn('the new key');

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('updateTinyUrl')
            ->with(99, ['target_url_hash' => 'the new hash', 'urlkey' => 'the new key']);

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            '99',
            $fieldArray,
            $this->getDataHandlerMock()
        );

        $this->assertArraySubset(['urlkey' => 'the new key'], $fieldArray);
    }

    public function testGeneratesKeyForNewRecord()
    {
        $fieldArray = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn(['target_url' => 'the target url', 'target_url_hash' => '']);

        $this->urlUtilsMock->expects($this->once())
            ->method('generateTinyurlHash')
            ->willReturn('the hash');

        $this->urlUtilsMock->expects($this->once())
            ->method('generateTinyurlKeyForUid')
            ->with(123)
            ->willReturn('the key');


        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('updateTinyUrl')
            ->with(123, ['target_url_hash' => 'the hash', 'urlkey' => 'the key']);

        $dataHandlerMock = $this->getDataHandlerMock();
        $dataHandlerMock->substNEWwithIDs['NEW1234'] = '123';

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            'NEW1234',
            $fieldArray,
            $dataHandlerMock
        );

        $this->assertArraySubset(['target_url_hash' => 'the hash', 'urlkey' => 'the key'], $fieldArray);
    }

    public function testSkippsProcessingForNonTinyUrlTable()
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects($this->never())
            ->method('findTinyUrlByUid');

        $this->tinyUrlRepositoryMock->expects($this->never())
            ->method('updateTinyUrl');

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'dummyTable',
            'dummyId',
            $fieldArray,
            $this->getDataHandlerMock()
        );

        $this->assertEquals($fieldArrayOriginal, $fieldArray);
    }

    public function testSkipsProcessingIfTinyUrlIsNotFoundInDatabase()
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willThrowException(new TinyUrlNotFoundException('not found'));

        $this->tinyUrlRepositoryMock->expects($this->never())
            ->method('updateTinyUrl');

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            55,
            $fieldArray,
            $this->getDataHandlerMock()
        );

        $this->assertEquals($fieldArrayOriginal, $fieldArray);
    }

    /**
     * @return DataHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataHandlerMock()
    {
        return $this->createMock(DataHandler::class);
    }
}
