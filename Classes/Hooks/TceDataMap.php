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

use Tx\Tinyurls\Domain\Repository\TinyUrlDatabaseRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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
     * The DataHandler instance that calls the hook.
     *
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @var array
     */
    protected $existingTinyUrlData;

    /**
     * @var bool
     */
    protected $isNewRecord;

    /**
     * @var TinyUrlDatabaseRepository
     */
    protected $tinyUrlRepository;

    /**
     * Tiny URL utilities
     *
     * @var UrlUtils
     */
    protected $urlUtils;

    public function injectTinyUrlRepository(TinyUrlDatabaseRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    public function injectUrlUtils(UrlUtils $urlUtils)
    {
        $this->urlUtils = $urlUtils;
    }

    /**
     * When a user stores a tinyurl record in the Backend the urlkey and the target_url_hash will be updated
     *
     * @param string $status (reference) Status of the current operation, 'new' or 'update
     * @param string $table (refrence) The table currently processing data for
     * @param string $id (reference) The record uid currently processing data for, [integer] or [string] (like 'NEW...')
     * @param array $fieldArray (reference) The field array of a record
     * @param DataHandler $tcemain Reference to the TCEmain object that calls this hook
     * @see t3lib_TCEmain::hook_processDatamap_afterDatabaseOperations()
     */
    public function processDatamap_afterDatabaseOperations(
        /** @noinspection PhpUnusedParameterInspection */
        string $status,
        string $table,
        $id,
        array &$fieldArray,
        DataHandler $tcemain
    ) {
        if ($table != TinyUrlRepository::TABLE_URLS) {
            return;
        }

        $this->dataHandler = $tcemain;
        $this->isNewRecord = $this->isNewRecord($id);

        $tinyUrlId = $this->getTinyUrlIdFromDataHandlerIfNew($id);

        try {
            $this->existingTinyUrlData = $this->getTinyUrlRepository()->findTinyUrlByUid($tinyUrlId);
        } catch (TinyUrlNotFoundException $exception) {
            return;
        }

        $updateArray = $this->getUpdatedUrlData($tinyUrlId);

        if ($updateArray === []) {
            return;
        }

        // Update the data in the field array so that it is consistent with the data in the database.
        $fieldArray = array_merge($fieldArray, $updateArray);

        $this->getTinyUrlRepository()->updateTinyUrl($tinyUrlId, $updateArray);
    }

    protected function getTinyUrlIdFromDataHandlerIfNew($originalId): int
    {
        if ($this->isNewRecord) {
            $id = (int)$this->dataHandler->substNEWwithIDs[$originalId];
        } else {
            $id = (int)$originalId;
        }

        return $id;
    }

    protected function getTinyUrlRepository(): TinyUrlDatabaseRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = GeneralUtility::makeInstance(TinyUrlDatabaseRepository::class);
        }
        return $this->tinyUrlRepository;
    }

    protected function getUpdatedUrlData(int $tinyUrlId): array
    {
        $targetUrl = $this->existingTinyUrlData['target_url'];
        $newTargetUrlHash = $this->getUrlUtils()->generateTinyurlHash($targetUrl);
        $updateArray = [];

        if ($this->shouldRecordBeUpdated($newTargetUrlHash)) {
            $updateArray['target_url_hash'] = $newTargetUrlHash;
            $updateArray['urlkey'] = $this->getUrlUtils()->generateTinyurlKeyForUid($tinyUrlId);
        }

        return $updateArray;
    }

    protected function getUrlUtils(): UrlUtils
    {
        if ($this->urlUtils === null) {
            $this->urlUtils = GeneralUtility::makeInstance(UrlUtils::class);
        }
        return $this->urlUtils;
    }

    protected function isNewRecord($id): bool
    {
        return GeneralUtility::isFirstPartOfStr($id, 'NEW');
    }

    protected function shouldRecordBeUpdated(string $newTargetUrlHash): bool
    {
        if ($this->isNewRecord) {
            return true;
        }
        return $this->existingTinyUrlData['target_url_hash'] !== $newTargetUrlHash;
    }
}
