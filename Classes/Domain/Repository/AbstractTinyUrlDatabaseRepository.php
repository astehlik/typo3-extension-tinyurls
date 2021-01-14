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
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractTinyUrlDatabaseRepository
{
    /**
     * Contains the extension configration
     *
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var TinyUrlValidator
     */
    protected $tinyUrlValidator;

    /**
     * Stores the given URL in the database, returns the inserted UID.
     *
     * @param TinyUrl $tinyUrl
     * @return int
     */
    abstract protected function insertNewTinyUrlInDatabase(TinyUrl $tinyUrl): int;

    /**
     * Executes the callback within a transation.
     *
     * @param \Closure $callback
     * @return mixed
     */
    abstract protected function transactional(\Closure $callback);

    /**
     * Updates the given URL in the database.
     *
     * @param TinyUrl $tinyUrl
     * @return mixed
     */
    abstract protected function updateTinyUrl(TinyUrl $tinyUrl);

    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function insertNewTinyUrl(TinyUrl $tinyUrl)
    {
        $this->prepareTinyUrlForInsert($tinyUrl);

        $this->transactional(
            function () use ($tinyUrl) {
                $this->insertNewTinyUrlTransaction($tinyUrl);
            }
        );
    }

    public function setTinyUrlValidator(TinyUrlValidator $tinyUrlValidator)
    {
        $this->tinyUrlValidator = $tinyUrlValidator;
    }

    protected function createTinyUrlFromDatabaseRow(array $databaseRow): TinyUrl
    {
        return TinyUrl::createFromDatabaseRow($databaseRow);
    }

    /**
     * @return ExtensionConfiguration
     * @codeCoverageIgnore
     */
    protected function getExtensionConfiguration(): ExtensionConfiguration
    {
        if ($this->extensionConfiguration === null) {
            $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        }
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
            'pid' => (int)$tinyUrl->getPid(),
            'tstamp' => (int)$tinyUrl->getTstamp()->getTimestamp(),
            'counter' => (int)$tinyUrl->getCounter(),
            'comment' => $tinyUrl->getComment(),
            'urlkey' => $tinyUrl->getUrlkey(),
            'target_url' => $tinyUrl->getTargetUrl(),
            'target_url_hash' => $tinyUrl->getTargetUrlHash(),
            'delete_on_use' => (int)$tinyUrl->getDeleteOnUse(),
            'valid_until' => $tinyUrl->hasValidUntil() ? (int)$tinyUrl->getValidUntil()->getTimestamp() : 0,
        ];
    }

    /**
     * @return TinyUrlValidator
     * @codeCoverageIgnore
     */
    protected function getTinyUrlValidator(): TinyUrlValidator
    {
        if ($this->tinyUrlValidator === null) {
            $this->tinyUrlValidator = GeneralUtility::makeInstance(TinyUrlValidator::class);
        }
        return $this->tinyUrlValidator;
    }

    protected function insertNewTinyUrlTransaction(TinyUrl $tinyUrl)
    {
        $customUrlKey = $tinyUrl->hasCustomUrlKey() ? $tinyUrl->getCustomUrlKey() : null;

        $tinyUrlUid = $this->insertNewTinyUrlInDatabase($tinyUrl);

        $tinyUrl->persistPostProcessInsert($tinyUrlUid);

        // We need to save the tinyurl once more because persistPostProcessInsert genereates
        // the URL key depending on the new UID if no custom URL key is used.
        if ($customUrlKey === null) {
            $tinyUrl->regenerateUrlKey();
            $this->updateTinyUrl($tinyUrl);
        }
    }

    protected function prepareTinyUrlForInsert(TinyUrl $tinyUrl)
    {
        $this->validateTinyUrl($tinyUrl);
        $tinyUrl->setPid($this->extensionConfiguration->getUrlRecordStoragePid());
        $tinyUrl->persistPreProcess();
    }

    protected function prepareTinyUrlForUpdate(TinyUrl $tinyUrl)
    {
        if ($tinyUrl->isNew()) {
            throw new \InvalidArgumentException('Only existing TinyUrl records can be updated.');
        }

        $this->validateTinyUrl($tinyUrl);

        $tinyUrl->persistPreProcess();
    }

    protected function validateTinyUrl(TinyUrl $tinyUrl)
    {
        $validator = $this->getTinyUrlValidator();
        $result = $validator->validate($tinyUrl);
        if ($result->hasErrors()) {
            $validationError = GeneralUtility::makeInstance(TinyUrlValidationException::class);
            $validationError->setValidationResult($result);
            throw $validationError;
        }
    }
}
