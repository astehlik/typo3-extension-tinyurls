<?php
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

use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
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
     * @var ConfigUtils
     */
    protected $configUtils;

    /**
     * Initializes the extension configuration
     */
    public function __construct()
    {
        $this->configUtils = $this->getConfigUtils();
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
    protected function convertIntToBase62($base10Integer, $base62Dictionary)
    {

        $base62Integer = '';
        $base = 62;

        do {
            $base62Integer = $base62Dictionary[($base10Integer % $base)] . $base62Integer;
            $base10Integer = floor($base10Integer / $base);
        } while ($base10Integer > 0);

        return $base62Integer;
    }

    /**
     * Generates a speaking tinyurl based on the speaking url template
     *
     * @param $tinyUrlKey
     * @return string
     */
    public function createSpeakingTinyUrl($tinyUrlKey)
    {

        $speakingUrl = $this->configUtils->getExtensionConfigurationValue('speakingUrlTemplate');

        $speakingUrl = $this->getMarkerBasedTemplateService()->substituteMarker(
            $speakingUrl,
            '###TINY_URL_KEY###',
            $tinyUrlKey
        );

        $matches = array();
        preg_match_all('/###(.*?)###/', $speakingUrl, $matches);

        if (empty($matches[1])) {
            return $speakingUrl;
        }

        foreach ($matches[1] as $match) {
            $speakingUrl = $this->getMarkerBasedTemplateService()->substituteMarker(
                $speakingUrl,
                '###' . $match . '###',
                $this->getIndependentEnvironmentVariable($match)
            );
        }

        return $speakingUrl;
    }

    /**
     * Generates a unique tinyurl key for the record with the given UID
     *
     * @param int $insertedUid
     * @return array
     */
    public function generateTinyurlKeyForUid($insertedUid)
    {

        $tinyUrlKey = $this->convertIntToBase62(
            $insertedUid,
            $this->configUtils->getExtensionConfigurationValue('base62Dictionary')
        );

        $numberOfFillupChars =
            $this->configUtils->getExtensionConfigurationValue('minimalTinyurlKeyLength') - strlen($tinyUrlKey);

        if ($numberOfFillupChars < $this->configUtils->getExtensionConfigurationValue('minimalRandomKeyLength')) {
            $numberOfFillupChars = $this->configUtils->getExtensionConfigurationValue('minimalRandomKeyLength');
        }

        if ($numberOfFillupChars < 1) {
            return $tinyUrlKey;
        }

        $tinyUrlKey .= '-' . GeneralUtility::getRandomHexString($numberOfFillupChars);

        return $tinyUrlKey;
    }

    /**
     * Generates a sha1 hash of the given URL
     *
     * @param string $url
     * @return string
     */
    public function generateTinyurlHash($url)
    {
        return sha1($url);
    }

    /**
     * @param string $indpEnvKey
     * @return string
     */
    protected function getIndependentEnvironmentVariable($indpEnvKey)
    {
        return GeneralUtility::getIndpEnv($indpEnvKey);
    }

    /**
     * @return ConfigUtils
     */
    protected function getConfigUtils()
    {
        return GeneralUtility::makeInstance(ConfigUtils::class);
    }

    /**
     * @return MarkerBasedTemplateService
     */
    protected function getMarkerBasedTemplateService()
    {
        return GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }
}
