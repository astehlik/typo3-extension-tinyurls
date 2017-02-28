<?php
declare(strict_types = 1);
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

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Core\Database\DatabaseConnection;

class TinyUrlDatabaseRepository extends AbstractTinyUrlDatabaseRepository implements TinyUrlRepository
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * See: http://lists.typo3.org/pipermail/typo3-dev/2007-December/026936.html
     * Use of "set counter=counter+1" - avoiding race conditions
     *
     * @param TinyUrl $tinyUrl
     */
    public function countTinyUrlHit(TinyUrl $tinyUrl)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            static::TABLE_URLS,
            'uid=' . (int)$tinyUrl->getUid(),
            ['counter' => 'counter + 1'],
            ['counter']
        );
    }

    public function deleteTinyUrlByKey(string $tinyUrlKey)
    {
        $deleteWhereStatement = 'urlkey=' .
            $this->getDatabaseConnection()->fullQuoteStr($tinyUrlKey, static::TABLE_URLS);
        $deleteWhereStatement = $this->getExtensionConfiguration()->appendPidQuery($deleteWhereStatement);

        $this->getDatabaseConnection()->exec_DELETEquery(
            static::TABLE_URLS,
            $deleteWhereStatement
        );
    }

    public function findTinyUrlByKey(string $tinyUrlKey): TinyUrl
    {
        $selctWhereStatement = 'urlkey=' .
            $this->getDatabaseConnection()->fullQuoteStr($tinyUrlKey, static::TABLE_URLS);
        $selctWhereStatement = $this->getExtensionConfiguration()->appendPidQuery($selctWhereStatement);

        $result = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            static::TABLE_URLS,
            $selctWhereStatement
        );

        if (empty($result)) {
            throw new TinyUrlNotFoundException(
                sprintf('The tinyurl with the key %s was not found in the database.', $tinyUrlKey)
            );
        }

        return $this->createTinyUrlFromDatabaseRow($result);
    }

    /**
     * Finds the URL by the given target URL.
     *
     * @param string $targetUrl
     * @return TinyUrl
     * @throws TinyUrlNotFoundException
     */
    public function findTinyUrlByTargetUrl(string $targetUrl): TinyUrl
    {
        $selctWhereStatement = 'target_url_hash=' .
            $this->getDatabaseConnection()->fullQuoteStr($this->getTargetUrlHash($targetUrl), static::TABLE_URLS);
        $selctWhereStatement = $this->getExtensionConfiguration()->appendPidQuery($selctWhereStatement);

        $result = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            static::TABLE_URLS,
            $selctWhereStatement
        );

        if (empty($result)) {
            throw new TinyUrlNotFoundException(
                sprintf('No existing tinyurl was found in the database for the target URL %s.', $targetUrl)
            );
        }

        return $this->createTinyUrlFromDatabaseRow($result);
    }

    public function findTinyUrlByUid(int $uid): TinyUrl
    {
        $result = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            static::TABLE_URLS,
            'uid=' . $uid
        );

        if (empty($result)) {
            throw new TinyUrlNotFoundException(
                sprintf('The tinyurl with the uid %d was not found in the database.', $uid)
            );
        }

        return $this->createTinyUrlFromDatabaseRow($result);
    }

    public function insertNewTinyUrl(TinyUrl $tinyUrl)
    {
        $this->prepareTinyUrlForInsert($tinyUrl);

        try {
            $this->getDatabaseConnection()->sql_query('START TRANSACTION');

            $this->getDatabaseConnection()->exec_INSERTquery(
                static::TABLE_URLS,
                $this->getTinyUrlDatabaseData($tinyUrl)
            );

            $tinyUrl->persistPostProcessInsert((int)$this->getDatabaseConnection()->sql_insert_id());

            if ($tinyUrl->getUrlkey() === '') {
                $tinyUrl->regenerateUrlKey();
                $this->updateTinyUrl($tinyUrl);
            }

            $this->getDatabaseConnection()->sql_query('COMMIT');
        } catch (\Exception $e) {
            $this->getDatabaseConnection()->sql_query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Purges all invalid urls from the database
     */
    public function purgeInvalidUrls()
    {
        $purgeWhereStatement = 'valid_until>0 AND valid_until<' . time();
        $purgeWhereStatement = $this->getExtensionConfiguration()->appendPidQuery($purgeWhereStatement);

        $this->getDatabaseConnection()->exec_DELETEquery(
            static::TABLE_URLS,
            $purgeWhereStatement
        );
    }

    public function setDatabaseConnection(DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    public function updateTinyUrl(TinyUrl $tinyUrl)
    {
        $this->prepareTinyUrlForUpdate($tinyUrl);

        $newTinyUrlData = $this->getTinyUrlDatabaseData($tinyUrl);

        $this->getDatabaseConnection()->exec_UPDATEquery(
            static::TABLE_URLS,
            'uid=' . (int)$tinyUrl->getUid(),
            $newTinyUrlData
        );

        $tinyUrl->persistPostProcess();
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection(): DatabaseConnection
    {
        if ($this->databaseConnection === null) {
            $this->databaseConnection = $GLOBALS['TYPO3_DB'];
        }
        return $this->databaseConnection;
    }
}
