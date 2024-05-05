<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Database;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;

readonly class StoragePageQueryRestriction implements QueryRestrictionInterface
{
    public function __construct(
        protected int $storagePageUid,
    ) {}

    /**
     * Main method to build expressions for given tables.
     *
     * @param array $queriedTables Array of tables, where array key is table name and value potentially an alias
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     *
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];

        foreach ($queriedTables as $tableName => $tableAlias) {
            if ($tableName !== TinyUrlRepository::TABLE_URLS) {
                continue;
            }

            $fieldName = ($tableAlias ?: $tableName) . '.pid';
            $constraints[] = $expressionBuilder->eq($fieldName, $this->storagePageUid);
        }

        return $expressionBuilder->and(...$constraints);
    }

    public function getStoragePageUid(): int
    {
        return $this->storagePageUid;
    }
}
