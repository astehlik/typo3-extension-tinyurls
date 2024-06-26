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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Hooks\TceDataMap;
use Tx\Tinyurls\Utils\UrlUtilsInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class TceDataMapTest extends TestCase
{
    private TceDataMap $tceDataMapHook;

    private MockObject|TinyUrlRepository $tinyUrlRepositoryMock;

    private MockObject|UrlUtilsInterface $urlUtilsMock;

    protected function setUp(): void
    {
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);
        $this->urlUtilsMock = $this->createMock(UrlUtilsInterface::class);

        $this->tceDataMapHook = new TceDataMap($this->tinyUrlRepositoryMock, $this->urlUtilsMock);
    }

    public function testDoesNotRegenerateKeyForExistingUnchangedRecord(): void
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(false);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::never())
            ->method('updateTinyUrl');

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            '99',
            $fieldArray,
            $this->getDataHandlerMock(),
        );

        self::assertSame($fieldArrayOriginal, $fieldArray);
    }

    public function testGeneratesHashForChangedUrl(): void
    {
        $fieldArray = ['the field' => 'the value'];
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(true);
        $tinyUrlMock->method('getTargetUrlHash')->willReturn('the new hash');

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('updateTinyUrl')
            ->with($tinyUrlMock);

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            '99',
            $fieldArray,
            $this->getDataHandlerMock(),
        );

        self::assertSame('the new hash', $fieldArray['target_url_hash']);
    }

    public function testGeneratesKeyForChangedUrl(): void
    {
        $fieldArray = ['the field' => 'the value'];
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(99);
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(true);
        $tinyUrlMock->method('getUrlKey')->willReturn('the new key');

        $this->urlUtilsMock->expects(self::once())
            ->method('regenerateUrlKey')
            ->with($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('updateTinyUrl')
            ->with($tinyUrlMock);

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            '99',
            $fieldArray,
            $this->getDataHandlerMock(),
        );

        self::assertSame('the new key', $fieldArray['urlkey']);
    }

    public function testGeneratesKeyForNewRecord(): void
    {
        $fieldArray = ['the field' => 'the value'];
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(123);
        $tinyUrlMock->method('getTargetUrlHash')->willReturn('the hash');
        $tinyUrlMock->method('getTargetUrlHasChanged')->willReturn(true);
        $tinyUrlMock->method('getUrlKey')->willReturn('the key');

        $this->urlUtilsMock->expects(self::once())
            ->method('regenerateUrlKey');

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByUid')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('updateTinyUrl')
            ->with($tinyUrlMock);

        $dataHandlerMock = $this->getDataHandlerMock();
        $dataHandlerMock->substNEWwithIDs['NEW1234'] = '123';

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            'NEW1234',
            $fieldArray,
            $dataHandlerMock,
        );

        self::assertSame('the hash', $fieldArray['target_url_hash']);
        self::assertSame('the key', $fieldArray['urlkey']);
    }

    public function testSkippsProcessingForNonTinyUrlTable(): void
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects(self::never())
            ->method('findTinyUrlByUid');

        $this->tinyUrlRepositoryMock->expects(self::never())
            ->method('updateTinyUrl');

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'dummyTable',
            'dummyId',
            $fieldArray,
            $this->getDataHandlerMock(),
        );

        self::assertSame($fieldArrayOriginal, $fieldArray);
    }

    public function testSkipsProcessingIfTinyUrlIsNotFoundInDatabase(): void
    {
        $fieldArray = $fieldArrayOriginal = ['the field' => 'the value'];

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByUid')
            ->willThrowException(new TinyUrlNotFoundException('not found'));

        $this->tinyUrlRepositoryMock->expects(self::never())
            ->method('updateTinyUrl');

        $this->tceDataMapHook->processDatamap_afterDatabaseOperations(
            'dummyStatus',
            'tx_tinyurls_urls',
            55,
            $fieldArray,
            $this->getDataHandlerMock(),
        );

        self::assertSame($fieldArrayOriginal, $fieldArray);
    }

    private function getDataHandlerMock(): DataHandler|MockObject
    {
        return $this->createMock(DataHandler::class);
    }
}
