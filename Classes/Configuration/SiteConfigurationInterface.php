<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Configuration;

use TYPO3\CMS\Core\Site\Entity\SiteInterface;

interface SiteConfigurationInterface
{
    public function loadSiteConfiguration(?SiteInterface $site): array;
}
