<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Database\StoragePageQueryRestriction;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TinyUrlDoctrineRepository extends AbstractTinyUrlDatabaseRepository implements TinyUrlRepository
{
    protected ?ConnectionPool $databaseConnectionPool = null;

    /**
     * See: http://lists.typo3.org/pipermail/typo3-dev/2007-December/026936.html
     * Use of "set counter=counter+1" - avoiding race conditions.
     */
    public function countTinyUrlHit(TinyUrl $tinyUrl): TinyUrl
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->update(static::TABLE_URLS)
            ->set('counter', $tinyUrl->getCounter() + 1)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($tinyUrl->getUid(), \PDO::PARAM_INT)
                )
            )
            ->executeStatement();

        return $this->findTinyUrlByKey($tinyUrl->getUrlkey());
    }

    public function deleteTinyUrlByKey(string $tinyUrlKey): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete(static::TABLE_URLS)
            ->where($queryBuilder->expr()->eq('urlkey', $queryBuilder->createNamedParameter($tinyUrlKey)))
            ->executeStatement();
    }

    public function findTinyUrlByKey(string $tinyUrlKey): TinyUrl
    {
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->from(static::TABLE_URLS)
            ->where($queryBuilder->expr()->eq('urlkey', $queryBuilder->createNamedParameter($tinyUrlKey)))
            ->executeQuery()
            ->fetchAssociative();

        if (empty($result)) {
            throw new TinyUrlNotFoundException($tinyUrlKey);
        }

        return $this->createTinyUrlFromDatabaseRow($result);
    }

    public function findTinyUrlByTargetUrl(string $targetUrl): TinyUrl
    {
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->from(static::TABLE_URLS)
            ->where(
                $queryBuilder->expr()->eq(
                    'target_url_hash',
                    $queryBuilder->createNamedParameter($this->getTargetUrlHash($targetUrl))
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (empty($result)) {
            throw new TinyUrlNotFoundException(
                sprintf('No existing tinyurl was found in the database for the target URL %s.', $targetUrl)
            );
        }

        return $this->createTinyUrlFromDatabaseRow($result);
    }

    public function findTinyUrlByUid(int $uid): TinyUrl
    {
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->from(static::TABLE_URLS)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if (empty($result)) {
            throw new TinyUrlNotFoundException(
                sprintf('The tinyurl with the uid %d was not found in the database.', $uid)
            );
        }

        return $this->createTinyUrlFromDatabaseRow($result);
    }

    /**
     * Purges all invalid urls from the database.
     */
    public function purgeInvalidUrls(): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete(static::TABLE_URLS)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->gt('valid_until', 0),
                    $queryBuilder->expr()->lt(
                        'valid_until',
                        $queryBuilder->createNamedParameter(time(), \PDO::PARAM_INT)
                    )
                )
            )
            ->executeStatement();
    }

    public function setDatabaseConnectionPool(ConnectionPool $databaseConnectionPool): void
    {
        $this->databaseConnectionPool = $databaseConnectionPool;
    }

    public function updateTinyUrl(TinyUrl $tinyUrl): void
    {
        $this->prepareTinyUrlForUpdate($tinyUrl);

        $newTinyUrlData = $this->getTinyUrlDatabaseData($tinyUrl);

        $this->getDatabaseConnection()->update(
            static::TABLE_URLS,
            $newTinyUrlData,
            ['uid' => $tinyUrl->getUid()]
        );

        $tinyUrl->persistPostProcess();
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->getDatabaseConnectionPool()
            ->getConnectionForTable(static::TABLE_URLS);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getDatabaseConnectionPool(): ConnectionPool
    {
        if ($this->databaseConnectionPool === null) {
            $this->databaseConnectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        return $this->databaseConnectionPool;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getDatabaseConnectionPool()
            ->getQueryBuilderForTable(static::TABLE_URLS);

        $queryBuilder->getRestrictions()->removeAll();

        $storagePid = $this->getExtensionConfiguration()->getUrlRecordStoragePid();
        $storagePageRestriction = GeneralUtility::makeInstance(StoragePageQueryRestriction::class, $storagePid);
        $queryBuilder->getRestrictions()->add($storagePageRestriction);

        return $queryBuilder;
    }

    protected function insertNewTinyUrlInDatabase(TinyUrl $tinyUrl): int
    {
        $this->getDatabaseConnection()->insert(
            static::TABLE_URLS,
            $this->getTinyUrlDatabaseData($tinyUrl)
        );

        return (int)$this->getDatabaseConnection()->lastInsertId(static::TABLE_URLS);
    }

    protected function transactional(\Closure $callback): void
    {
        $this->getDatabaseConnection()->transactional($callback);
    }
}
