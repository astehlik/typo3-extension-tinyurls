<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Configuration;

use Tx\Tinyurls\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

readonly class SiteConfiguration implements SiteConfigurationInterface
{
    public function __construct(
        private SiteFinder $siteFinder,
    ) {}

    public function loadSiteConfiguration(?SiteInterface $site): array
    {
        if (!$site instanceof Site) {
            return [];
        }

        $siteConfig = $site->getConfiguration();

        $tinyUrlConfig = $siteConfig['tinyurls'] ?? [];

        $this->validateSiteConfiguration($site, $tinyUrlConfig);

        return $tinyUrlConfig;
    }

    private function validateSiteConfiguration(Site $site, array $config): void
    {
        $storagePid = (int)($config[ConfigKeys::URL_RECORD_STORAGE_PID] ?? 0);

        if ($storagePid <= 0) {
            return;
        }

        $expectedSite = $this->siteFinder->getSiteByPageId($storagePid);

        if ($expectedSite->getIdentifier() === $site->getIdentifier()) {
            return;
        }

        throw new InvalidConfigurationException(
            sprintf(
                'The site configuration for site "%s" is invalid. The configured %s %d'
                . ' does not belong to the site.',
                $site->getIdentifier(),
                ConfigKeys::URL_RECORD_STORAGE_PID,
                $storagePid,
            ),
        );
    }
}
