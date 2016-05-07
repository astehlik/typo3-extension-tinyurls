<?php
namespace Tx\Tinyurls\Hooks;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hooks for the TYPO3 core engine
 *
 * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
 * @author Sebastian Lemke <s.lemke.deleteme@infoworxx.de>
 */
class TceDataMap
{
    /**
     * Tiny URL utilities
     *
     * @var UrlUtils
     */
    var $urlUtils;

    /**
     * Initializes the URL utils
     */
    public function __construct()
    {
        $this->urlUtils = GeneralUtility::makeInstance(UrlUtils::class);
    }

    /**
     * When a user stores a tinyurl record in the Backend the urlkey and the target_url_hash will be updated
     *
     * @param string $status (reference) Status of the current operation, 'new' or 'update
     * @param string $table (refrence) The table currently processing data for
     * @param string $id (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
     * @param array $fieldArray (reference) The field array of a record
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tcemain Reference to the TCEmain object that calls this hook
     * @see t3lib_TCEmain::hook_processDatamap_afterDatabaseOperations()
     */
    public function processDatamap_afterDatabaseOperations(
        /** @noinspection PhpUnusedParameterInspection */
        $status,
        $table,
        $id,
        &$fieldArray,
        $tcemain
    ) {

        if ($table != 'tx_tinyurls_urls') {
            return;
        }

        $regenerateUrlKey = false;

        if (GeneralUtility::isFirstPartOfStr($id, 'NEW')) {
            $id = $tcemain->substNEWwithIDs[$id];
            $regenerateUrlKey = true;
        }

        $tinyUrlData = BackendUtility::getRecord('tx_tinyurls_urls', $id);
        $updateArray['target_url_hash'] = $this->urlUtils->generateTinyurlHash($tinyUrlData['target_url']);

        // If the hash has changed we regenerate the URL key
        if ($updateArray['target_url_hash'] !== $tinyUrlData['target_url_hash']) {
            $regenerateUrlKey = true;
        }

        if ($regenerateUrlKey) {
            $updateArray['urlkey'] = $this->urlUtils->generateTinyurlKeyForUid($id);
        }

        // Update the data in the field array so that it is consistent
        // with the data in the database.
        $fieldArray = array_merge($fieldArray, $updateArray);

        $this->getDatabaseConnection()->exec_UPDATEquery('tx_tinyurls_urls', 'uid=' . $id, $updateArray);
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
