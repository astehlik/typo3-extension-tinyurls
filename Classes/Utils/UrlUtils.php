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
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Contains utilities for creating tiny url keys and url hashes.
 */
readonly class UrlUtils implements UrlUtilsInterface
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private GeneralUtilityWrapper $generalUtility,
        private SiteFinder $siteFinder,
        private UrlKeyGenerator $urlKeyGenerator,
    ) {}

    public function buildTinyUrl(string $tinyUrlKey): string
    {
        if ($this->extensionConfiguration->areSpeakingUrlsEnabled()) {
            return $this->createSpeakingTinyUrl($tinyUrlKey);
        }

        return $this->createEidUrl($tinyUrlKey);
    }

    public function buildTinyUrlForPid(string $urlkey, int $pid): string
    {
        $site = $this->getSiteByPid($pid);

        return $this->buildTinyUrlForSite($urlkey, $site);
    }

    public function buildTinyUrlForSite(string $urlkey, ?SiteInterface $site): string
    {
        $this->extensionConfiguration->setSite($site);

        $tinyUrl = $this->buildTinyUrl($urlkey);

        $this->extensionConfiguration->reset();

        return $tinyUrl;
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
                $this->getPlaceholderValue($match),
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
        // @extensionScannerIgnoreLine
        return $this->getBaseUrl()
            . '?eID=tx_tinyurls&tx_tinyurls[key]=' . $tinyUrlKey;
    }

    private function getBaseUrl(): string
    {
        /** @extensionScannerIgnoreLine */
        $baseUrl = $this->extensionConfiguration->getBaseUrl();

        if ($baseUrl === null) {
            return $this->generalUtility->getNormalizedParams()->getSiteUrl();
        }

        return (string)$baseUrl;
    }

    private function getPlaceholderValue(string $match): string
    {
        if ($match === 'TYPO3_SITE_URL') {
            // @extensionScannerIgnoreLine
            return $this->getBaseUrl();
        }

        $normalizedParams = $this->generalUtility->getNormalizedParams();

        return match ($match) {
            'HTTP_HOST' => $normalizedParams->getHttpHost(),
            'HTTP_REFERER' => $normalizedParams->getHttpReferer(),
            'HTTP_USER_AGENT' => $normalizedParams->getHttpUserAgent(),
            'HTTP_ACCEPT_ENCODING' => $normalizedParams->getHttpAcceptEncoding(),
            'HTTP_ACCEPT_LANGUAGE' => $normalizedParams->getHttpAcceptLanguage(),
            'PATH_INFO' => $normalizedParams->getPathInfo(),
            'QUERY_STRING' => $normalizedParams->getQueryString(),
            'REMOTE_ADDR' => $normalizedParams->getRemoteAddress(),
            'REMOTE_HOST' => $normalizedParams->getRemoteHost(),
            // @extensionScannerIgnoreLine
            'REQUEST_URI' => $normalizedParams->getRequestUri(),
            'SCRIPT_FILENAME' => $normalizedParams->getScriptFilename(),
            'SCRIPT_NAME' => $normalizedParams->getScriptName(),
            'TYPO3_DOCUMENT_ROOT' => $normalizedParams->getDocumentRoot(),
            'TYPO3_HOST_ONLY' => $normalizedParams->getRequestHostOnly(),
            'TYPO3_PORT' => (string)$normalizedParams->getRequestPort(),
            'TYPO3_REQUEST_DIR' => $normalizedParams->getRequestDir(),
            'TYPO3_REQUEST_HOST' => $normalizedParams->getRequestHost(),
            'TYPO3_REQUEST_SCRIPT' => $normalizedParams->getRequestScript(),
            'TYPO3_REQUEST_URL' => $normalizedParams->getRequestUrl(),
            'TYPO3_REV_PROXY', 'TYPO3_PROXY' => $normalizedParams->isBehindReverseProxy() ? '1' : '',
            'TYPO3_SITE_PATH' => $normalizedParams->getSitePath(),
            'TYPO3_SITE_SCRIPT' => $normalizedParams->getSiteScript(),
            'TYPO3_SSL' => $normalizedParams->isHttps() ? '1' : '',
            default => '',
        };
    }

    private function getSiteByPid(int $pid): ?SiteInterface
    {
        try {
            return $this->siteFinder->getSiteByPageId($pid);
        } catch (SiteNotFoundException) {
            return null;
        }
    }
}
