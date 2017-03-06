<?php
declare(strict_types = 1);
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

use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList;

/**
 * Hook for improving the display of tinyurls in the list module.
 *
 * We make this a singleton to improve the performance. We can cache the urldisplay query.
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
     * @var UrlUtils
     */
    protected $urlUtils;

    /**
     * @param UrlUtils $urlUtils
     */
    public function injectUrlUtils(UrlUtils $urlUtils)
    {
        $this->urlUtils = $urlUtils;
    }

    /**
     * Called by the makeQueryArray hook in the DatabaseRecordList.
     * Adjust the SELECT query for getting a nice value for the urldisplay field of a tiny URL record.
     *
     * @param array $queryParts
     * @param AbstractDatabaseRecordList $parentRecordList
     * @param string $table
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function makeQueryArray_post(
        array &$queryParts,
        /** @noinspection PhpUnusedParameterInspection */
        AbstractDatabaseRecordList $parentRecordList,
        string $table
    ) {
        if ($table !== TinyUrlRepository::TABLE_URLS) {
            return;
        }
        if (strpos($queryParts['SELECT'], 'urldisplay') === false) {
            return;
        }

        if ($this->urlDisplayQuery === null) {
            $urlUtils = $this->getUrlUtils();
            $this->urlDisplayQuery = "CONCAT('" . $urlUtils->createSpeakingTinyUrl("', urlkey, '") . "') as urldisplay";
        }

        $queryParts['SELECT'] = str_replace('urldisplay', $this->urlDisplayQuery, $queryParts['SELECT']);
    }

    /**
     * @return UrlUtils
     * @codeCoverageIgnore
     */
    protected function getUrlUtils(): UrlUtils
    {
        if ($this->urlUtils === null) {
            $this->urlUtils = GeneralUtility::makeInstance(UrlUtils::class);
        }
        return $this->urlUtils;
    }
}
