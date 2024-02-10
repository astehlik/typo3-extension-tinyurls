<?php

declare(strict_types=1);

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
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Hooks for the TYPO3 core engine.
 *
 * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
 * @author Sebastian Lemke <s.lemke.deleteme@infoworxx.de>
 */
class TceDataMap
{
    public function __construct(
        protected readonly TinyUrlRepository $tinyUrlRepository,
        protected readonly UrlUtils $urlUtils
    ) {}

    /**
     * When a user stores a tinyurl record in the Backend the urlkey and the target_url_hash will be updated.
     *
     * @param string $status (reference) Status of the current operation, 'new' or 'update
     * @param string $table (refrence) The table currently processing data for
     * @param int|string $recordId (reference) Uid of the currently processed record, [int] or [string] (like 'NEW...')
     * @param array $fieldArray (reference) The field array of a record
     * @param DataHandler $tcemain Reference to the TCEmain object that calls this hook
     *
     * @see DataHandler::hook_processDatamap_afterDatabaseOperations()
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function processDatamap_afterDatabaseOperations(
        /** @noinspection PhpUnusedParameterInspection */
        string $status,
        string $table,
        int|string $recordId,
        array &$fieldArray,
        DataHandler $tcemain
    ): void {
        if ($table !== TinyUrlRepository::TABLE_URLS) {
            return;
        }

        $tinyUrlId = $this->getTinyUrlIdFromDataHandlerIfNew($recordId, $tcemain);

        try {
            $tinyUrl = $this->tinyUrlRepository->findTinyUrlByUid($tinyUrlId);
        } catch (TinyUrlNotFoundException) {
            return;
        }

        $updateArray = $this->updateTinyUrlAndGetChangedFields($tinyUrl, $this->isNewRecord($recordId));

        if ($updateArray === []) {
            return;
        }

        // Update the data in the field array so that it is consistent with the data in the database.
        $fieldArray = array_merge($fieldArray, $updateArray);

        $this->tinyUrlRepository->updateTinyUrl($tinyUrl);
    }

    protected function getTinyUrlIdFromDataHandlerIfNew($originalId, DataHandler $dataHandler): int
    {
        if ($this->isNewRecord($originalId)) {
            return (int)$dataHandler->substNEWwithIDs[$originalId];
        }

        return (int)$originalId;
    }

    protected function isNewRecord($recordId): bool
    {
        return str_contains((string)$recordId, 'NEW');
    }

    protected function shouldUrlKeyBeRegenerated(TinyUrl $tinyUrl, bool $isNewRecord): bool
    {
        if ($isNewRecord) {
            return true;
        }

        return $tinyUrl->getTargetUrlHasChanged();
    }

    protected function updateTinyUrlAndGetChangedFields(TinyUrl $tinyUrl, bool $isNewRecord): array
    {
        $updateArray = [];

        if ($tinyUrl->getTargetUrlHasChanged()) {
            $updateArray['target_url_hash'] = $tinyUrl->getTargetUrlHash();
        }

        if ($this->shouldUrlKeyBeRegenerated($tinyUrl, $isNewRecord)) {
            $this->urlUtils->regenerateUrlKey($tinyUrl);
            $updateArray['urlkey'] = $tinyUrl->getUrlkey();
        }

        return $updateArray;
    }
}
