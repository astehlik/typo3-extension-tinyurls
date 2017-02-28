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

    public function buildTinyUrl(string $tinyUrlKey)
    {
        if ($this->extensionConfiguration->areSpeakingUrlsEnabled()) {
            $tinyUrl = $this->createSpeakingTinyUrl($tinyUrlKey);
            return $tinyUrl;
        } else {
            $tinyUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
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
     * @param string $url
     * @return string
     */
    public function generateTinyurlHash(string $url): string
    {
        return sha1($url);
    }

    /**
     * Generates a unique tinyurl key for the record with the given UID
     *
     * @param int $insertedUid
     * @return string
     */
    public function generateTinyurlKeyForUid(int $insertedUid): string
    {
        $tinyUrlKey = $this->convertIntToBase62(
            $insertedUid,
            $this->getExtensionConfiguration()->getBase62Dictionary()
        );

        $numberOfFillupChars =
            $this->getExtensionConfiguration()->getMinimalTinyurlKeyLength() - strlen($tinyUrlKey);

        $minimalRandomKeyLength = $this->getExtensionConfiguration()->getMinimalRandomKeyLength();
        if ($numberOfFillupChars < $minimalRandomKeyLength) {
            $numberOfFillupChars = $minimalRandomKeyLength;
        }

        if ($numberOfFillupChars < 1) {
            return $tinyUrlKey;
        }

        $tinyUrlKey .= '-' . $this->getGeneralUtility()->getRandomHexString($numberOfFillupChars);

        return $tinyUrlKey;
    }

    /**
     * This mehtod converts the given base 10 integer to a base62
     *
     * Thanks to http://jeremygibbs.com/2012/01/16/how-to-make-a-url-shortener
     *
     * @param int $base10Integer The integer that will converted
     * @param string $base62Dictionary the dictionary for generating the base62 integer
     * @return string A base62 encoded integer using a custom dictionary
     */
    protected function convertIntToBase62(int $base10Integer, string $base62Dictionary): string
    {
        $base62Integer = '';
        $base = 62;

        do {
            $base62Integer = $base62Dictionary[($base10Integer % $base)] . $base62Integer;
            $base10Integer = floor($base10Integer / $base);
        } while ($base10Integer > 0);

        return $base62Integer;
    }

    protected function getExtensionConfiguration(): ExtensionConfiguration
    {
        if ($this->extensionConfiguration === null) {
            $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        }
        return $this->extensionConfiguration;
    }

    protected function getGeneralUtility(): GeneralUtilityWrapper
    {
        if ($this->generalUtility === null) {
            $this->generalUtility = GeneralUtility::makeInstance(GeneralUtilityWrapper::class);
        }
        return $this->generalUtility;
    }
}
