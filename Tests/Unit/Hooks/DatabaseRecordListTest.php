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
use TYPO3\CMS\Core\Database\Query\QueryBuilder as Typo3QueryBuilder;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Contains a hook for the typolink generation to convert a typolink
 * in a tinyurl. Additionally, it contains a public api for generating
 * a tinyurl in another extension.
 */
class DatabaseRecordListTest extends TestCase
{
    /**
     * @var DatabaseRecordListHooks
     */
    protected $databaseRecordListHooks;

    /**
     * @var DatabaseRecordList
     */
    protected $parentRecordListMock;

    /**
     * @var MockObject|UrlUtils
     */
    private $urlUtilsMock;

    protected function setUp(): void
    {
        $this->parentRecordListMock = $this->createMock(DatabaseRecordList::class);
        $this->urlUtilsMock = $this->createMock(UrlUtils::class);
        $this->databaseRecordListHooks = new DatabaseRecordListHooks($this->urlUtilsMock);
    }

    public function testQueryDoesNotChangeForNonDefaultFields()
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);

        $this->callModifyQuery($queryBuilder, TinyUrlRepository::TABLE_URLS, ['otherdisplay']);
    }

    public function testQueryDoesNotChangeForOtherTable()
    {
        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);
        $this->callModifyQuery($queryBuilder, 'someother_table');
    }

    public function testQueryDoesNotChangeIfTinyUrlIsEmpty()
    {
        $this->expectBuildTinyUrlCall('');

        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);
        $this->callModifyQuery($queryBuilder);
    }

    public function testQueryDoesNotChangeIfTinyUrlOnlyContainsUrlKey()
    {
        $this->expectBuildTinyUrlCall('###urlkey###');

        $queryBuilder = $this->createQueryBuilderMock();
        $this->assertQueryDoesNotChange($queryBuilder);
        $this->callModifyQuery($queryBuilder);
    }

    public function testUrlDisplaySelectIsAdded()
    {
        $this->expectBuildTinyUrlCall();

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder->expects($this->once())
            ->method('addSelectLiteral')
            ->with("CONCAT('https://myurl.tld/goto/', `urlkey`) as urldisplay");

        $this->callModifyQuery($queryBuilder);
    }

    public function testUrlDisplaySelectIsAddedWithSuffix()
    {
        $this->expectBuildTinyUrlCall('https://myurl.tld/goto/###urlkey###/suffix');

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder->expects($this->once())
            ->method('addSelectLiteral')
            ->with("CONCAT('https://myurl.tld/goto/', `urlkey`, '/suffix') as urldisplay");

        $this->callModifyQuery($queryBuilder);
    }

    public function testUrlDisplaySelectIsAddedFromCache()
    {
        $this->expectBuildTinyUrlCall();

        $queryBuilder = $this->createQueryBuilderMock();
        $queryBuilder->expects($this->exactly(2))
            ->method('addSelectLiteral')
            ->with("CONCAT('https://myurl.tld/goto/', `urlkey`) as urldisplay");

        // Fill the cache.
        $this->callModifyQuery($queryBuilder);

        $this->callModifyQuery($queryBuilder);
    }

    /**
     * @param Typo3QueryBuilder|MockObject $queryBuilder
     */
    private function assertQueryDoesNotChange(Typo3QueryBuilder $queryBuilder)
    {
        $queryBuilder->expects($this->never())->method('addSelectLiteral');
    }

    private function callModifyQuery(
        Typo3QueryBuilder $queryBuilder,
        $table = TinyUrlRepository::TABLE_URLS,
        $fieldList = ['*']
    ): void {
        $this->databaseRecordListHooks->modifyQuery([], $table, 1, [], $fieldList, $queryBuilder);
    }

    /**
     * @return MockObject|Typo3QueryBuilder
     */
    private function createQueryBuilderMock(): Typo3QueryBuilder
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
        $this->urlUtilsMock->expects($this->once())
            ->method('buildTinyUrl')
            ->with('###urlkey###')
            ->willReturn($returnValue);
    }
}
