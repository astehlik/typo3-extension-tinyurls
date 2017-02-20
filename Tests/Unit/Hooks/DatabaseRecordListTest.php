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
use Tx\Tinyurls\Hooks\DatabaseRecordList as DatabaseRecordListHooks;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList;

/**
 * Contains a hook for the typolink generation to convert a typolink
 * in a tinyurl. Additionally, it contains a public api for generating
 * a tinyurl in another extension.
 */
class DatabaseRecordList extends TestCase
{
    /**
     * @var DatabaseRecordListHooks
     */
    protected $databaseRecordListHooks;

    /**
     * @var AbstractDatabaseRecordList
     */
    protected $parentRecordListMock;

    protected function setUp()
    {
        $this->parentRecordListMock = $this->createMock(AbstractDatabaseRecordList::class);
        $this->databaseRecordListHooks = new DatabaseRecordListHooks();
    }

    public function testmakeQueryArrayPostDoesNotChangeQueryPartsForOtherField()
    {
        $queryPartsOriginal = $queryParts = ['SELECT' => 'otherdisplay'];
        $this->databaseRecordListHooks->makeQueryArray_post(
            $queryParts,
            $this->parentRecordListMock,
            'tx_tinyurls_urls'
        );
        static::assertEquals($queryPartsOriginal, $queryParts);
    }

    public function testmakeQueryArrayPostDoesNotChangeQueryPartsForOtherTable()
    {
        $queryPartsOriginal = $queryParts = ['SELECT' => 'urldisplay'];
        $this->databaseRecordListHooks->makeQueryArray_post($queryParts, $this->parentRecordListMock, 'othertable');
        static::assertEquals($queryPartsOriginal, $queryParts);
    }

    public function testmakeQueryArrayPostReplacesUrldisplayWithConcatQuery()
    {
        $urlUtilsMock = $this->getUrlUtilsMock();
        $urlUtilsMock->expects($this->once())
            ->method('createSpeakingTinyUrl')
            ->with("', urlkey, '")
            ->willReturn('http://myurl.tld/goto/' . "', urlkey, '");
        $this->databaseRecordListHooks->injectUrlUtils($urlUtilsMock);

        $queryPartsExcpected = ['SELECT' => "CONCAT('http://myurl.tld/goto/', urlkey, '') as urldisplay"];
        $queryParts = ['SELECT' => 'urldisplay'];
        $this->databaseRecordListHooks->makeQueryArray_post(
            $queryParts,
            $this->parentRecordListMock,
            'tx_tinyurls_urls'
        );
        static::assertEquals($queryPartsExcpected, $queryParts);
    }

    public function testmakeQueryArrayPostReplacesUrldisplayWithConcatQueryFromCache()
    {
        $urlUtilsMock = $this->getUrlUtilsMock();
        $urlUtilsMock->expects($this->once())
            ->method('createSpeakingTinyUrl')
            ->with("', urlkey, '")
            ->willReturn('http://myurl.tld/goto/' . "', urlkey, '");
        $this->databaseRecordListHooks->injectUrlUtils($urlUtilsMock);

        // Fill the cache.
        $queryParts = ['SELECT' => 'urldisplay'];
        $this->databaseRecordListHooks->makeQueryArray_post(
            $queryParts,
            $this->parentRecordListMock,
            'tx_tinyurls_urls'
        );

        $queryPartsExcpected = ['SELECT' => "CONCAT('http://myurl.tld/goto/', urlkey, '') as urldisplay"];
        $queryParts = ['SELECT' => 'urldisplay'];
        $this->databaseRecordListHooks->makeQueryArray_post(
            $queryParts,
            $this->parentRecordListMock,
            'tx_tinyurls_urls'
        );
        static::assertEquals($queryPartsExcpected, $queryParts);
    }

    /**
     * @return UrlUtils|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUrlUtilsMock(): UrlUtils
    {
        return $this->createMock(UrlUtils::class);
    }
}
