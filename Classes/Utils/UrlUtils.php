<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Utils;

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
use Tx\Tinyurls\Object\ImplementationManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains utilities for creating tiny url keys and url hashes
 */
class UrlUtils implements SingletonInterface
{
    /**
     * Contains the extension configration
     *
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var GeneralUtilityWrapper
     */
    protected $generalUtility;

    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function injectGeneralUtility(GeneralUtilityWrapper $generalUtility)
    {
        $this->generalUtility = $generalUtility;
    }

    public function buildTinyUrl(string $tinyUrlKey): string
    {
        if ($this->getExtensionConfiguration()->areSpeakingUrlsEnabled()) {
            $tinyUrl = $this->createSpeakingTinyUrl($tinyUrlKey);
            return $tinyUrl;
        } else {
            $tinyUrl = $this->getGeneralUtility()->getIndpEnv('TYPO3_SITE_URL');
            $tinyUrl .= '?eID=tx_tinyurls&tx_tinyurls[key]=' . $tinyUrlKey;
            return $tinyUrl;
        }
    }

    /**
     * Generates a speaking tinyurl based on the speaking url template
     *
     * @param string $tinyUrlKey
     * @return string
     */
    public function createSpeakingTinyUrl(string $tinyUrlKey): string
    {
        $speakingUrl = $this->getExtensionConfiguration()->getSpeakingUrlTemplate();

        $speakingUrl = str_replace('###TINY_URL_KEY###', $tinyUrlKey, $speakingUrl);

        $matches = [];
        preg_match_all('/###(.*?)###/', $speakingUrl, $matches);

        if (empty($matches[1])) {
            return $speakingUrl;
        }

        foreach ($matches[1] as $match) {
            $speakingUrl = str_replace(
                '###' . $match . '###',
                $this->getGeneralUtility()->getIndpEnv($match),
                $speakingUrl
            );
        }

        return $speakingUrl;
    }

    /**
     * Generates a sha1 hash of the given URL
     *
     * @deprecated Use TinyUrl model for hash generation instead.
     * @param string $url
     * @return string
     */
    public function generateTinyurlHash(string $url): string
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl($url);
        return $tinyUrl->getTargetUrlHash();
    }

    /**
     * Generates a unique tinyurl key for the record with the given UID
     *
     * @deprecated Use the UrlKeyGenerator for generating the URL key.
     * @param int $uid
     * @return string
     */
    public function generateTinyurlKeyForUid(int $uid): string
    {
        $urlKeyGenerator = ImplementationManager::getInstance()->getUrlKeyGenerator();
        return $urlKeyGenerator->generateTinyurlKeyForUid($uid);
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

    /**
     * @return GeneralUtilityWrapper
     * @codeCoverageIgnore
     */
    protected function getGeneralUtility(): GeneralUtilityWrapper
    {
        if ($this->generalUtility === null) {
            $this->generalUtility = GeneralUtility::makeInstance(GeneralUtilityWrapper::class);
        }
        return $this->generalUtility;
    }
}
