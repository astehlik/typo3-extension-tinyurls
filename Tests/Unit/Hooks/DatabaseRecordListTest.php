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
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Hooks\DatabaseRecordList as DatabaseRecordListHooks;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as Typo3QueryBuilder;

/**
 * Contains a hook for the typolink generation to convert a typolink
 * in a tinyurl. Additionally, it contains a public api for generating
 * a tinyurl in another extension.
 */
class DatabaseRecordListTest extends TestCase
{
    private DatabaseRecordListHooks $databaseRecordListHooks;

    private MockObject|DatabaseRecordList $parentRecordListMock;

    private MockObject|UrlUtils $urlUtilsMock;

    protected function setUp(): void
    {
        $this->parentRecordListMock = $this->createMock(DatabaseRecordList::class);
        $this->urlUtilsMock = $this->createMock(UrlUtils::class);
        $this->databaseRecordListHooks = new DatabaseRecordListHooks($this->urlUtilsMock);
    }

    public function testQueryDoesNotChangeForNonDefaultFields(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);

        $this->callModifyQuery($queryBuilder, TinyUrlRepository::TABLE_URLS, ['otherdisplay']);
    }

    public function testQueryDoesNotChangeForOtherTable(): void
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);
        $this->callModifyQuery($queryBuilder, 'someother_table');
    }

    public function testQueryDoesNotChangeIfTinyUrlIsEmpty(): void
    {
        $this->expectBuildTinyUrlCall('');

        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);
        $this->callModifyQuery($queryBuilder);
    }

    public function testQueryDoesNotChangeIfTinyUrlOnlyContainsUrlKey(): void
    {
        $this->expectBuildTinyUrlCall('###urlkey###');

        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);
        $this->callModifyQuery($queryBuilder);
    }

    public function testUrlDisplaySelectIsAdded(): void
    {
        $this->expectBuildTinyUrlCall();

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder->expects(self::once())
            ->method('addSelectLiteral')
            ->with("CONCAT('https://myurl.tld/goto/', `urlkey`) as urldisplay");

        $this->callModifyQuery($queryBuilder);
    }

    public function testUrlDisplaySelectIsAddedFromCache(): void
    {
        $this->expectBuildTinyUrlCall();

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder->expects(self::exactly(2))
            ->method('addSelectLiteral')
            ->with("CONCAT('https://myurl.tld/goto/', `urlkey`) as urldisplay");

        // Fill the cache.
        $this->callModifyQuery($queryBuilder);

        $this->callModifyQuery($queryBuilder);
    }

    public function testUrlDisplaySelectIsAddedWithSuffix(): void
    {
        $this->expectBuildTinyUrlCall('https://myurl.tld/goto/###urlkey###/suffix');

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder->expects(self::once())
            ->method('addSelectLiteral')
            ->with("CONCAT('https://myurl.tld/goto/', `urlkey`, '/suffix') as urldisplay");

        $this->callModifyQuery($queryBuilder);
    }

    private function assertQueryDoesNotChange(MockObject|Typo3QueryBuilder $queryBuilder): void
    {
        $queryBuilder->expects(self::never())->method('addSelectLiteral');
    }

    private function callModifyQuery(
        Typo3QueryBuilder $queryBuilder,
        $table = TinyUrlRepository::TABLE_URLS,
        $fieldList = ['*']
    ): void {
        $event = new ModifyDatabaseQueryForRecordListingEvent(
            $queryBuilder,
            $table,
            12,
            $fieldList,
            1,
            20,
            $this->parentRecordListMock
        );

        $this->databaseRecordListHooks->__invoke($event);
    }

    private function createQueryBuilderMock(): MockObject|Typo3QueryBuilder
    {
        $queryBuilder = $this->getMockBuilder(Typo3QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->method('quote')->willReturnCallback(
            function (string $value) {
                return "'" . $value . "'";
            }
        );
        $queryBuilder->method('quoteIdentifier')->willReturnCallback(
            function (string $value) {
                return '`' . $value . '`';
            }
        );

        return $queryBuilder;
    }

    private function expectBuildTinyUrlCall(string $returnValue = 'https://myurl.tld/goto/###urlkey###'): void
    {
        $this->urlUtilsMock->expects(self::once())
            ->method('buildTinyUrl')
            ->with('###urlkey###')
            ->willReturn($returnValue);
    }
}
