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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList;

/**
 * Contains a hook for the typolink generation to convert a typolink
 * in a tinyurl. Additionally, it contains a public api for generating
 * a tinyurl in another extension.
 */
class DatabaseRecordList implements SingletonInterface
{
    /**
     * Cache for the URL display query.
     *
     * @var string
     */
    protected $urlDisplayQuery;

    /**
     * Called by the makeQueryArray hook in the DatabaseRecordList.
     * Adjust the SELECT query for getting a nice value for the urldisplay field of a tiny URL record.
     *
     * @param array $queryParts
     * @param AbstractDatabaseRecordList $parentRecordList
     * @param string $table
     * @throws \InvalidArgumentException
     */
    public function makeQueryArray_post(
        array &$queryParts,
        /** @noinspection PhpUnusedParameterInspection */
        AbstractDatabaseRecordList $parentRecordList,
        $table
    ) {
        if ($table !== 'tx_tinyurls_urls') {
            return;
        }
        if (!strpos($queryParts['SELECT'], 'urldisplay')) {
            return;
        }

        if ($this->urlDisplayQuery === null) {
            $urlUtils = GeneralUtility::makeInstance(UrlUtils::class);
            $this->urlDisplayQuery = "CONCAT('" . $urlUtils->createSpeakingTinyUrl("', urlkey, '") . "') as urldisplay";
        }

        $queryParts['SELECT'] = str_replace('urldisplay', $this->urlDisplayQuery, $queryParts['SELECT']);
    }
}
