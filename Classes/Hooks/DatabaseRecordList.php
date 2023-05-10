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
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as Typo3QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for improving the display of tinyurls in the list module.
 *
 * We make this a singleton to improve the performance. We can cache the urldisplay query.
 */
class DatabaseRecordList implements SingletonInterface
{
    /**
     * Cache for the URL display query.
     */
    protected ?string $urlDisplayQuery = '';

    public function __construct(private readonly UrlUtils $urlUtils)
    {
    }

    public function __invoke(ModifyDatabaseQueryForRecordListingEvent $modifyQueryEvent): void {
        if ($modifyQueryEvent->getTable() !== TinyUrlRepository::TABLE_URLS) {
            return;
        }

        if ($modifyQueryEvent->getFields() !== ['*']) {
            return;
        }

        $this->buildDisplayQuery($modifyQueryEvent->getQueryBuilder());

        if (!$this->urlDisplayQuery) {
            return;
        }

        $modifyQueryEvent->getQueryBuilder()->addSelectLiteral($this->urlDisplayQuery);
    }

    protected function buildDisplayQuery(Typo3QueryBuilder $queryBuilder): void
    {
        if ($this->urlDisplayQuery !== null) {
            return;
        }

        $tinyUrl = $this->urlUtils->buildTinyUrl('###urlkey###');
        $tinyUrlParts = GeneralUtility::trimExplode('###urlkey###', $tinyUrl, true, 2);
        if (count($tinyUrlParts) === 0) {
            return;
        }

        $quotedUrlParts = array_map(
            function (string $urlPart) use ($queryBuilder) {
                return $queryBuilder->quote($urlPart);
            },
            $tinyUrlParts
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
