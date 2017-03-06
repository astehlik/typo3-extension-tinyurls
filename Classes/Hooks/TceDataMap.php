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

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Object\ImplementationManager;
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
     * @var bool
     */
    protected $isNewRecord;

    /**
     * @var TinyUrl
     */
    protected $tinyUrl;

    /**
     * @var TinyUrlRepository
     */
    protected $tinyUrlRepository;

    public function injectTinyUrlRepository(TinyUrlRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    /**
     * When a user stores a tinyurl record in the Backend the urlkey and the target_url_hash will be updated
     *
     * @param string $status (reference) Status of the current operation, 'new' or 'update
     * @param string $table (refrence) The table currently processing data for
     * @param string $recordId (reference) Uid of the currently processed record, [integer] or [string] (like 'NEW...')
     * @param array $fieldArray (reference) The field array of a record
     * @param DataHandler $tcemain Reference to the TCEmain object that calls this hook
     * @see t3lib_TCEmain::hook_processDatamap_afterDatabaseOperations()
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function processDatamap_afterDatabaseOperations(
        /** @noinspection PhpUnusedParameterInspection */
        string $status,
        string $table,
        $recordId,
        array &$fieldArray,
        DataHandler $tcemain
    ) {
        if ($table != TinyUrlRepository::TABLE_URLS) {
            return;
        }

        $this->dataHandler = $tcemain;
        $this->isNewRecord = $this->isNewRecord($recordId);

        $tinyUrlId = $this->getTinyUrlIdFromDataHandlerIfNew($recordId);

        try {
            $this->tinyUrl = $this->getTinyUrlRepository()->findTinyUrlByUid($tinyUrlId);
        } catch (TinyUrlNotFoundException $exception) {
            return;
        }

        $updateArray = $this->updateTinyUrlAndGetChangedFields();

        if ($updateArray === []) {
            return;
        }

        // Update the data in the field array so that it is consistent with the data in the database.
        $fieldArray = array_merge($fieldArray, $updateArray);

        $this->getTinyUrlRepository()->updateTinyUrl($this->tinyUrl);
    }

    protected function getTinyUrlIdFromDataHandlerIfNew($originalId): int
    {
        if ($this->isNewRecord) {
            $tinyUrlId = (int)$this->dataHandler->substNEWwithIDs[$originalId];
        } else {
            $tinyUrlId = (int)$originalId;
        }

        return $tinyUrlId;
    }

    /**
     * @return TinyUrlRepository
     * @codeCoverageIgnore
     */
    protected function getTinyUrlRepository(): TinyUrlRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = ImplementationManager::getInstance()->getTinyUrlRepository();
        }
        return $this->tinyUrlRepository;
    }

    protected function isNewRecord($recordId): bool
    {
        return GeneralUtility::isFirstPartOfStr($recordId, 'NEW');
    }

    protected function shouldUrlKeyBeRegenerated(): bool
    {
        if ($this->isNewRecord) {
            return true;
        }
        return $this->tinyUrl->getTargetUrlHasChanged();
    }

    protected function updateTinyUrlAndGetChangedFields(): array
    {
        $updateArray = [];

        if ($this->tinyUrl->getTargetUrlHasChanged()) {
            $updateArray['target_url_hash'] = $this->tinyUrl->getTargetUrlHash();
        }

        if ($this->shouldUrlKeyBeRegenerated()) {
            $this->tinyUrl->regenerateUrlKey();
            $updateArray['urlkey'] = $this->tinyUrl->getUrlkey();
        }

        return $updateArray;
    }
}
