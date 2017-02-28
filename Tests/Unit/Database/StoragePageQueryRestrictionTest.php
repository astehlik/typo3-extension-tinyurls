<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Unit\Database;

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
use Tx\Tinyurls\Database\StoragePageQueryRestriction;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

class StoragePageQueryRestrictionTest extends TestCase
{
    /**
     * @var ExpressionBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $expressionBuilderMock;

    /**
     * @var \Tx\Tinyurls\Database\StoragePageQueryRestriction
     */
    protected $storagePageQueryRestriction;

    protected function setUp()
    {
        $this->expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $this->storagePageQueryRestriction = new StoragePageQueryRestriction(38);
    }

    public function testBuildExpressionReturnsEmptyContraintForNonTinyUrlTable()
    {
        $this->expressionBuilderMock->expects($this->never())
            ->method('eq');

        $expression = $this->storagePageQueryRestriction->buildExpression(
            ['the_table' => 'the_alias'],
            $this->expressionBuilderMock
        );
        $this->assertEquals(0, $expression->count());
    }

    public function testBuildExpressionReturnsStoragePageContraintForTinyUrlTable()
    {
        $this->expressionBuilderMock->expects($this->once())
            ->method('eq')
            ->with('the_alias.pid', 38)
            ->willReturn('the constraint');

        $constraints[] = 'the constraint';
        $andContraintMock = $this->createMock(CompositeExpression::class);

        $this->expressionBuilderMock->expects($this->once())
            ->method('andX')
            ->with(...$constraints)
            ->willReturn($andContraintMock);

        $expression = $this->storagePageQueryRestriction->buildExpression(
            [TinyUrlRepository::TABLE_URLS => 'the_alias'],
            $this->expressionBuilderMock
        );

        $this->assertEquals($andContraintMock, $expression);
    }

    public function testBuildExpressionUsesTableNameIfAliasIsEmpty()
    {
        $this->expressionBuilderMock->expects($this->once())
            ->method('eq')
            ->with(TinyUrlRepository::TABLE_URLS . '.pid', 38)
            ->willReturn('the constraint');

        $this->storagePageQueryRestriction->buildExpression(
            [TinyUrlRepository::TABLE_URLS => ''],
            $this->expressionBuilderMock
        );
    }
}
