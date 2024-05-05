<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Psr\Http\Message\UriInterface;
use Symfony\Contracts\Service\ResetInterface;
use Tx\Tinyurls\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration as TYPO3ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Contains utilities for getting configuration.
 */
class ExtensionConfiguration implements ResetInterface
{
    /**
     * The initialized extension configuration.
     */
    protected ?ExtensionConfigurationData $extensionConfiguration = null;

    private ?SiteInterface $site = null;

    public function __construct(
        protected readonly SiteFinder $siteFinder,
        protected readonly TYPO3ExtensionConfiguration $typo3extensionConfiguration,
    ) {}

    /**
     * Appends a PID query to the given where statement.
     *
     * @param string $whereStatement The where statement where the PID query should be appended to
     *
     * @return string The where statement with the appended PID query
     *
     * @deprecated method is not used any more and will be removed in next major version
     */
    public function appendPidQuery(string $whereStatement): string
    {
        if (!empty($whereStatement)) {
            $whereStatement .= ' AND ';
        }

        $whereStatement .= 'pid=' . $this->getUrlRecordStoragePid();

        return $whereStatement;
    }

    public function areSpeakingUrlsEnabled(): bool
    {
        return $this->getExtensionConfigurationData()->createSpeakingUrls;
    }

    public function getBase62Dictionary(): string
    {
        return $this->getExtensionConfigurationData()->base62Dictionary;
    }

    public function getBaseUrl(): ?UriInterface
    {
        if (
            $this->extensionConfiguration->baseUrlFromSiteBase
            && $this->site instanceof Site
        ) {
            return $this->site->getBase();
        }

        // @extensionScannerIgnoreLine
        if ($this->getExtensionConfigurationData()->baseUrl === '') {
            return null;
        }

        // @extensionScannerIgnoreLine
        return new Uri($this->getExtensionConfigurationData()->baseUrl);
    }

    public function getMinimalRandomKeyLength(): int
    {
        return $this->getExtensionConfigurationData()->minimalRandomKeyLength;
    }

    public function getMinimalTinyurlKeyLength(): int
    {
        return $this->getExtensionConfigurationData()->minimalTinyurlKeyLength;
    }

    public function getSpeakingUrlTemplate(): string
    {
        return $this->getExtensionConfigurationData()->speakingUrlTemplate;
    }

    public function getUrlRecordStoragePid(): int
    {
        return $this->getExtensionConfigurationData()->urlRecordStoragePid;
    }

    public function reset(): void
    {
        $this->setSite(null);
    }

    public function setSite(?SiteInterface $site): void
    {
        $this->site = $site;

        $this->extensionConfiguration = null;
    }

    protected function getExtensionConfigurationData(): ExtensionConfigurationData
    {
        if ($this->extensionConfiguration !== null) {
            return $this->extensionConfiguration;
        }

        $extensionConfiguration = array_merge($this->loadExtensionConfiguration(), $this->loadSiteConfiguration());

        $this->extensionConfiguration = ExtensionConfigurationData::fromArray($extensionConfiguration);

        return $this->extensionConfiguration;
    }

    private function loadExtensionConfiguration(): array
    {
        return $this->typo3extensionConfiguration->get('tinyurls');
    }

    private function loadSiteConfiguration(): array
    {
        $site = $this->site;

        if (!$site instanceof Site) {
            return [];
        }

        $siteConfig = $site->getConfiguration();

        $tinyUrlConfig =  $siteConfig['tinyurls'] ?? [];

        $this->validateSiteConfiguration($tinyUrlConfig);

        return $tinyUrlConfig;
    }

    private function validateSiteConfiguration(array $config): void
    {
        $storagePid = (int)($config[ConfigKeys::URL_RECORD_STORAGE_PID] ?? 0);

        if ($storagePid <= 0) {
            return;
        }

        $expectedSite = $this->siteFinder->getSiteByPageId($storagePid);

        if ($expectedSite->getIdentifier() === $this->site->getIdentifier()) {
            return;
        }

        throw new InvalidConfigurationException(
            sprintf(
                'The site configuration for site "%s" is invalid. The configured %s %d'
                . ' does not belong to the site.',
                $this->site->getIdentifier(),
                ConfigKeys::URL_RECORD_STORAGE_PID,
                $storagePid,
            ),
        );
    }
}
