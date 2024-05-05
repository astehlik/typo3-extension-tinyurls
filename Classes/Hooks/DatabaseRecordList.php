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

use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Utils\UrlUtilsInterface;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for improving the display of tinyurls in the list module.
 *
 * We make this a singleton to improve the performance. We can cache the urldisplay query.
 */
class DatabaseRecordList
{
    /**
     * Cache for the URL display query.
     */
    protected ?string $urlDisplayQuery = null;

    public function __construct(
        private readonly UrlUtilsInterface $urlUtils,
    ) {}

    public function __invoke(ModifyDatabaseQueryForRecordListingEvent $modifyQueryEvent): void
    {
        if ($modifyQueryEvent->getTable() !== TinyUrlRepository::TABLE_URLS) {
            return;
        }

        if (
            $modifyQueryEvent->getFields() !== ['*']
            && !in_array('urldisplay', $modifyQueryEvent->getFields(), true)
        ) {
            return;
        }

        $this->buildDisplayQuery($modifyQueryEvent);

        if (!$this->urlDisplayQuery) {
            return;
        }

        $modifyQueryEvent->getQueryBuilder()->addSelectLiteral($this->urlDisplayQuery);
    }

    protected function buildDisplayQuery(ModifyDatabaseQueryForRecordListingEvent $modifyQueryEvent): void
    {
        if ($this->urlDisplayQuery !== null) {
            return;
        }

        $queryBuilder = $modifyQueryEvent->getQueryBuilder();

        $tinyUrl = $this->urlUtils->buildTinyUrlForPid('###urlkey###', $modifyQueryEvent->getPageId());

        $tinyUrlParts = GeneralUtility::trimExplode('###urlkey###', $tinyUrl, true, 2);
        if (count($tinyUrlParts) === 0) {
            return;
        }

        $quotedUrlParts = array_map(
            static fn(string $urlPart) => $queryBuilder->quote($urlPart),
            $tinyUrlParts,
        );
        $concatParts = [
            $quotedUrlParts[0],
            $queryBuilder->quoteIdentifier('urlkey'),
        ];
        if (count($tinyUrlParts) === 2) {
            $concatParts[] = $quotedUrlParts[1];
        }

        $this->urlDisplayQuery = 'CONCAT(' . implode(', ', $concatParts) . ') as urldisplay';
    }
}
