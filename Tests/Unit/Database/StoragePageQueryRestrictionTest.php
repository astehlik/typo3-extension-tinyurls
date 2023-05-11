<?php

declare(strict_types=1);

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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Database\StoragePageQueryRestriction;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

class StoragePageQueryRestrictionTest extends TestCase
{
    /**
     * @var ExpressionBuilder|MockObject
     */
    protected $expressionBuilderMock;

    /**
     * @var StoragePageQueryRestriction
     */
    protected $storagePageQueryRestriction;

    protected function setUp(): void
    {
        if (!interface_exists('TYPO3\\CMS\\Core\\Database\\Query\\Restriction\\QueryRestrictionInterface')) {
            self::markTestSkipped('The new Doctrine DBAL QueryRestrictionInterface does not exist.');
        }
        $this->expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $this->storagePageQueryRestriction = new StoragePageQueryRestriction(38);
    }

    public function testBuildExpressionReturnsEmptyContraintForNonTinyUrlTable(): void
    {
        $this->expressionBuilderMock->expects(self::never())
            ->method('eq');

        $expression = $this->storagePageQueryRestriction->buildExpression(
            ['the_table' => 'the_alias'],
            $this->expressionBuilderMock
        );
        self::assertSame(0, $expression->count());
    }

    public function testBuildExpressionReturnsStoragePageContraintForTinyUrlTable(): void
    {
        $this->expressionBuilderMock->expects(self::once())
            ->method('eq')
            ->with('the_alias.pid', 38)
            ->willReturn('the constraint');

        $constraints[] = 'the constraint';
        $andContraintMock = $this->createMock(CompositeExpression::class);

        $this->expressionBuilderMock->expects(self::once())
            ->method('and')
            ->with(...$constraints)
            ->willReturn($andContraintMock);

        $expression = $this->storagePageQueryRestriction->buildExpression(
            [TinyUrlRepository::TABLE_URLS => 'the_alias'],
            $this->expressionBuilderMock
        );

        self::assertSame($andContraintMock, $expression);
    }

    public function testBuildExpressionUsesTableNameIfAliasIsEmpty(): void
    {
        $this->expressionBuilderMock->expects(self::once())
            ->method('eq')
            ->with(TinyUrlRepository::TABLE_URLS . '.pid', 38)
            ->willReturn('the constraint');

        $this->storagePageQueryRestriction->buildExpression(
            [TinyUrlRepository::TABLE_URLS => ''],
            $this->expressionBuilderMock
        );
    }
}
