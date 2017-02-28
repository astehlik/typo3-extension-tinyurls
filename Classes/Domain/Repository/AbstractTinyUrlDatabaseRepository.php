<?php
declare(strict_types = 1);
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
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    protected function createTinyUrlFromDatabaseRow(array $databaseRow): TinyUrl
    {
        return TinyUrl::createFromDatabaseRow($databaseRow);
    }

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
            'valid_until' => (int)$tinyUrl->getValidUntil()->getTimestamp(),
        ];
    }

    protected function prepareTinyUrlForInsert(TinyUrl $tinyUrl)
    {
        $this->validateTinyUrl($tinyUrl);
        $tinyUrl->setPid($this->extensionConfiguration->getUrlRecordStoragePid());
        $tinyUrl->persistPreProcess();
    }

    protected function validateTinyUrl(TinyUrl $tinyUrl)
    {
        $validator = GeneralUtility::makeInstance(TinyUrlValidator::class);
        $result = $validator->validate($tinyUrl);
        if ($result->hasErrors()) {
            $validationError = GeneralUtility::makeInstance(TinyUrlValidationException::class);
            $validationError->setValidationResult($result);
            throw $validationError;
        }
    }
}
