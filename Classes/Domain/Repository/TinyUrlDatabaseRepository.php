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

use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TinyUrlDatabaseRepository implements SingletonInterface
{
    /**
     * Contains the extension configration
     *
     * @var \Tx\Tinyurls\Configuration\ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * See: http://lists.typo3.org/pipermail/typo3-dev/2007-December/026936.html
     * Use of "set counter=counter+1" - avoiding race conditions
     *
     * @param int $tinyUrlUid
     */
    public function countTinyUrlHit(int $tinyUrlUid)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_tinyurls_urls',
            'uid=' . $tinyUrlUid,
            ['counter' => 'counter + 1'],
            ['counter']
        );
    }

    public function deleteTinyUrlByKey(string $tinyUrlKey)
    {
        $deleteWhereStatement = 'urlkey=' .
            $this->getDatabaseConnection()->fullQuoteStr($tinyUrlKey, 'tx_tinyurls_urls');
        $deleteWhereStatement = $this->getExtensionConfiguration()->appendPidQuery($deleteWhereStatement);

        $this->getDatabaseConnection()->exec_DELETEquery(
            'tx_tinyurls_urls',
            $deleteWhereStatement
        );
    }

    public function findTinyUrlByKey(string $tinyUrlKey): array
    {
        $selctWhereStatement = 'urlkey=' .
            $this->getDatabaseConnection()->fullQuoteStr($tinyUrlKey, 'tx_tinyurls_urls');
        $selctWhereStatement = $this->getExtensionConfiguration()->appendPidQuery($selctWhereStatement);

        $result = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid,urlkey,target_url,delete_on_use',
            'tx_tinyurls_urls',
            $selctWhereStatement
        );

        if (empty($result)) {
            throw new TinyUrlNotFoundException(
                sprintf('The tinyurl with the key %s was not found in the database.', $tinyUrlKey)
            );
        }

        return $result;
    }

    public function findTinyUrlByUid(int $uid): array
    {
        $result = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'tx_tinyurls_urls',
            'uid=' . $uid
        );

        if (empty($result)) {
            throw new TinyUrlNotFoundException(
                sprintf('The tinyurl with the uid %d was not found in the database.', $uid)
            );
        }

        return $result;
    }

    /**
     * Purges all invalid urls from the database
     */
    public function purgeInvalidUrls()
    {
        $purgeWhereStatement = 'valid_until>0 AND valid_until<' . time();
        $purgeWhereStatement = $this->getExtensionConfiguration()->appendPidQuery($purgeWhereStatement);

        $this->getDatabaseConnection()->exec_DELETEquery(
            'tx_tinyurls_urls',
            $purgeWhereStatement
        );
    }

    public function updateTinyUrl(int $tinyUrlUid, array $newTinyUrlData)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery('tx_tinyurls_urls', 'uid=' . $tinyUrlUid, $newTinyUrlData);
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection(): DatabaseConnection
    {
        return $GLOBALS['TYPO3_DB'];
    }

    protected function getExtensionConfiguration(): ExtensionConfiguration
    {
        if ($this->extensionConfiguration === null) {
            $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        }
        return $this->extensionConfiguration;
    }
}
