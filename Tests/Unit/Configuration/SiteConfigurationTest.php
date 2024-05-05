<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Configuration\ConfigKeys;
use Tx\Tinyurls\Configuration\SiteConfiguration;
use Tx\Tinyurls\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

class SiteConfigurationTest extends TestCase
{
    private SiteConfiguration $siteConfiguration;

    private MockObject|SiteFinder $siteFinderMock;

    protected function setUp(): void
    {
        $this->siteFinderMock = $this->createMock(SiteFinder::class);

        $this->siteConfiguration = new SiteConfiguration($this->siteFinderMock);
    }

    public function testLoadSiteConfigurationReturnsValidStoragePid(): void
    {
        $siteMock = $this->createSiteMock();

        $this->initializeSiteFinderMock('site-identifier');

        self::assertSame(
            [ConfigKeys::URL_RECORD_STORAGE_PID => 123],
            $this->siteConfiguration->loadSiteConfiguration($siteMock),
        );
    }

    public function testLoadSiteConfigurationValidatesStoragePid(): void
    {
        $siteMock = $this->createSiteMock();

        $this->initializeSiteFinderMock('site-identifier-pid');

        $this->expectException(InvalidConfigurationException::class);

        $this->siteConfiguration->loadSiteConfiguration($siteMock);
    }

    private function createSiteMock(): MockObject|Site
    {
        $siteMock = $this->createMock(Site::class);

        $siteMock->method('getConfiguration')
            ->willReturn(['tinyurls' => [ConfigKeys::URL_RECORD_STORAGE_PID => 123]]);

        $siteMock->method('getIdentifier')->willReturn('site-identifier');
        return $siteMock;
    }

    private function initializeSiteFinderMock(string $siteIdentifier): void
    {
        $pidSiteMock = $this->createMock(Site::class);
        $pidSiteMock->method('getIdentifier')
            ->willReturn($siteIdentifier);

        $this->siteFinderMock->expects(self::once())
            ->method('getSiteByPageId')
            ->with(123)
            ->willReturn($pidSiteMock);
    }
}
