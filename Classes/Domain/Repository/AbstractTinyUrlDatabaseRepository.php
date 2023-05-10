<?php

declare(strict_types=1);

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
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Validator\TinyUrlValidator;
use Tx\Tinyurls\Exception\TinyUrlValidationException;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractTinyUrlDatabaseRepository
{
    private TinyUrlValidator $tinyUrlValidator;

    /**
     * Stores the given URL in the database, returns the inserted UID.
     */
    abstract protected function insertNewTinyUrlInDatabase(TinyUrl $tinyUrl): int;

    /**
     * Executes the callback within a transation.
     */
    abstract protected function transactional(\Closure $callback): void;

    /**
     * Updates the given URL in the database.
     */
    abstract protected function updateTinyUrl(TinyUrl $tinyUrl): void;

    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly UrlUtils $urlUtils,
    ) {
        $this->tinyUrlValidator = new TinyUrlValidator($this);
    }

    public function insertNewTinyUrl(TinyUrl $tinyUrl): void
    {
        $this->prepareTinyUrlForInsert($tinyUrl);

        $this->transactional(
            function () use ($tinyUrl): void {
                $this->insertNewTinyUrlTransaction($tinyUrl);
            }
        );
    }

    /**
     * @internal for testing purposes only
     */
    public function overrideTinyUrlValidator(TinyUrlValidator $tinyUrlValidator): void
    {
        $this->tinyUrlValidator = $tinyUrlValidator;
    }

    protected function createTinyUrlFromDatabaseRow(array $databaseRow): TinyUrl
    {
        return TinyUrl::createFromDatabaseRow($databaseRow);
    }

    /**
     * @codeCoverageIgnore
     *
     * @deprecated Will be removed in next major version, use class variable instead
     */
    protected function getExtensionConfiguration(): ExtensionConfiguration
    {
        return $this->extensionConfiguration;
    }

    protected function getTargetUrlHash(string $targetUrl): string
    {
        $tinyUrl = new TinyUrl();
        $tinyUrl->setTargetUrl($targetUrl);
        return $tinyUrl->getTargetUrlHash();
    }

    protected function getTinyUrlDatabaseData(TinyUrl $tinyUrl): array
    {
        return [
            'pid' => $tinyUrl->getPid(),
            'tstamp' => $tinyUrl->getTstamp()->getTimestamp(),
            'counter' => $tinyUrl->getCounter(),
            'comment' => $tinyUrl->getComment(),
            'urlkey' => $tinyUrl->getUrlkey(),
            'target_url' => $tinyUrl->getTargetUrl(),
            'target_url_hash' => $tinyUrl->getTargetUrlHash(),
            'delete_on_use' => (int)$tinyUrl->getDeleteOnUse(),
            'valid_until' => $tinyUrl->hasValidUntil() ? $tinyUrl->getValidUntil()->getTimestamp() : 0,
        ];
    }

    /**
     * @codeCoverageIgnore
     *
     * @deprecated will be removed in next major version, class variable instead
     */
    protected function getTinyUrlValidator(): TinyUrlValidator
    {
        return $this->tinyUrlValidator;
    }

    protected function insertNewTinyUrlTransaction(TinyUrl $tinyUrl): void
    {
        $customUrlKey = $tinyUrl->hasCustomUrlKey() ? $tinyUrl->getCustomUrlKey() : null;

        $tinyUrlUid = $this->insertNewTinyUrlInDatabase($tinyUrl);

        $tinyUrl->persistPostProcessInsert($tinyUrlUid);

        // We need to save the tinyurl once more because persistPostProcessInsert genereates
        // the URL key depending on the new UID if no custom URL key is used.
        if ($customUrlKey === null) {
            $this->urlUtils->regenerateUrlKey($tinyUrl);
            $this->updateTinyUrl($tinyUrl);
        }
    }

    protected function prepareTinyUrlForInsert(TinyUrl $tinyUrl): void
    {
        $this->validateTinyUrl($tinyUrl);
        $tinyUrl->setPid($this->extensionConfiguration->getUrlRecordStoragePid());
        $tinyUrl->persistPreProcess();
    }

    protected function prepareTinyUrlForUpdate(TinyUrl $tinyUrl): void
    {
        if ($tinyUrl->isNew()) {
            throw new \InvalidArgumentException('Only existing TinyUrl records can be updated.');
        }

        $this->validateTinyUrl($tinyUrl);

        $tinyUrl->persistPreProcess();
    }

    protected function validateTinyUrl(TinyUrl $tinyUrl): void
    {
        $result = $this->tinyUrlValidator->validate($tinyUrl);

        if (!$result->hasErrors()) {
            return;
        }

        $validationError = GeneralUtility::makeInstance(TinyUrlValidationException::class);
        $validationError->setValidationResult($result);
        throw $validationError;
    }
}
