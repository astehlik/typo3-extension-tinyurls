<?php

declare(strict_types=1);

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
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Contains utilities for creating tiny url keys and url hashes.
 */
class UrlUtils implements SingletonInterface
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly GeneralUtilityWrapper $generalUtility,
        private readonly UrlKeyGenerator $urlKeyGenerator,
    ) {}

    public function buildTinyUrl(string $tinyUrlKey): string
    {
        if ($this->extensionConfiguration->areSpeakingUrlsEnabled()) {
            return $this->createSpeakingTinyUrl($tinyUrlKey);
        }

        return $this->createEidUrl($tinyUrlKey);
    }

    /**
     * Generates a speaking tinyurl based on the speaking url template.
     */
    public function createSpeakingTinyUrl(string $tinyUrlKey): string
    {
        $speakingUrl = $this->extensionConfiguration->getSpeakingUrlTemplate();

        $speakingUrl = str_replace('###TINY_URL_KEY###', $tinyUrlKey, $speakingUrl);

        $matches = [];
        preg_match_all('/###(.*?)###/', $speakingUrl, $matches);

        if (empty($matches[1])) {
            return $speakingUrl;
        }

        foreach ($matches[1] as $match) {
            $speakingUrl = str_replace(
                '###' . $match . '###',
                $this->generalUtility->getIndpEnv($match),
                $speakingUrl,
            );
        }

        return $speakingUrl;
    }

    /**
     * Generates a sha1 hash of the given URL.
     *
     * @deprecated use TinyUrl model for hash generation instead
     */
    public function generateTinyurlHash(string $url): string
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl($url);
        return $tinyUrl->getTargetUrlHash();
    }

    /**
     * Generates a unique tinyurl key for the record with the given UID.
     *
     * @deprecated use the UrlKeyGenerator for generating the URL key or regenerateUrlKey() instead
     */
    public function generateTinyurlKeyForUid(int $uid): string
    {
        return $this->urlKeyGenerator->generateTinyurlKeyForUid($uid);
    }

    public function regenerateUrlKey(TinyUrl $tinyUrl): void
    {
        $urlKey = $this->urlKeyGenerator->generateTinyurlKeyForTinyUrl($tinyUrl);

        $tinyUrl->setGeneratedUrlKey($urlKey);
    }

    protected function createEidUrl(string $tinyUrlKey): string
    {
        return $this->generalUtility->getIndpEnv('TYPO3_SITE_URL')
            . '?eID=tx_tinyurls&tx_tinyurls[key]=' . $tinyUrlKey;
    }
}
