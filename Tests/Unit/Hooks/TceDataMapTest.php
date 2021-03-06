<?php

declare(strict_types=1);

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

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Hooks\TceDataMap;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class TceDataMapTest extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * @var TceDataMap
     */
    protected $tceDataMapHook;

    /**
     * @var TinyUrlRepository|MockObject
     */
    protected $tinyUrlRepositoryMock;

    protected function setUp(): void
    {
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);

        $this->tceDataMapHook = new TceDataMap();
        $this->tceDataMapHook->injectTinyUrlRepository($this->tinyUrlRepositoryMock);
    }

    public function testDoesNotRegenerateKeyForExistingUnchangedRecord()
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(false);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

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
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(true);
        $tinyUrlMock->method('getTargetUrlHash')->willReturn('the new hash');

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('updateTinyUrl')
            ->with($tinyUrlMock);

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
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(99);
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(true);
        $tinyUrlMock->method('getUrlKey')->willReturn('the new key');

        $tinyUrlMock->expects($this->once())
            ->method('regenerateUrlKey');

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('updateTinyUrl')
            ->with($tinyUrlMock);

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
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(123);
        $tinyUrlMock->method('getTargetUrlHash')->willReturn('the hash');
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(true);
        $tinyUrlMock->method('getUrlKey')->willReturn('the key');

        $tinyUrlMock->expects($this->once())
            ->method('regenerateUrlKey');

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('updateTinyUrl')
            ->with($tinyUrlMock);

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
     * @return DataHandler|MockObject
     */
    protected function getDataHandlerMock()
    {
        return $this->createMock(DataHandler::class);
    }
}
