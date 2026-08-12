<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Utils;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Tests for the tinyurls API.
 */
class UrlUtilsTest extends TestCase
{
    private ExtensionConfiguration|MockObject $extensionConfigurationMock;

    private GeneralUtilityWrapper|MockObject $generalUtilityMock;

    private MockObject|SiteFinder $siteFinderMock;

    private MockObject|UrlKeyGenerator $urlKeyGeneratorMock;

    private UrlUtils $urlUtils;

    protected function setUp(): void
    {
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->generalUtilityMock = $this->createMock(GeneralUtilityWrapper::class);
        $this->siteFinderMock = $this->createMock(SiteFinder::class);
        $this->urlKeyGeneratorMock = $this->createMock(UrlKeyGenerator::class);

        $this->urlUtils = new UrlUtils(
            $this->extensionConfigurationMock,
            $this->generalUtilityMock,
            $this->siteFinderMock,
            $this->urlKeyGeneratorMock,
        );
    }

    #[BackupGlobals(true)]
    public function testBuildTinyUrlCreatesEidUrlIfSpeakingUrlsAreDisabled(): void
    {
        $normalizedParamsMock = $this->createMock(NormalizedParams::class);
        $normalizedParamsMock->expects($this->once())
            ->method('getSiteUrl')
            ->willReturn('http://the-site.url/');

        $this->generalUtilityMock->expects($this->once())
            ->method('getNormalizedParams')
            ->willReturn($normalizedParamsMock);

        $this->extensionConfigurationMock->expects($this->once())
            ->method('areSpeakingUrlsEnabled')
            ->willReturn(false);

        $this->assertSame(
            'http://the-site.url/?eID=tx_tinyurls&tx_tinyurls[key]=thekey',
            $this->urlUtils->buildTinyUrl('thekey'),
        );
    }

    public function testBuildTinyUrlCreatesSpeakingUrlIfEnabled(): void
    {
        $this->extensionConfigurationMock
            ->method('getSpeakingUrlTemplate')
            ->willReturn('http://base.url/###TINY_URL_KEY###');

        $this->extensionConfigurationMock->expects($this->once())
            ->method('areSpeakingUrlsEnabled')
            ->willReturn(true);

        $this->assertSame('http://base.url/thekey', $this->urlUtils->buildTinyUrl('thekey'));
    }

    public function testBuildTinyUrlForPidSetsAndResetsSiteInExtensionConfiguration(): void
    {
        $siteMock = $this->createMock(Site::class);

        $this->extensionConfigurationMock->expects($this->once())
            ->method('setSite')
            ->with($siteMock);

        $this->extensionConfigurationMock->expects($this->once())
            ->method('reset');

        $this->siteFinderMock->expects($this->once())
            ->method('getSiteByPageId')
            ->with(123)
            ->willReturn($siteMock);

        $this->urlUtils->buildTinyUrlForPid('thekey', 123);
    }

    public function testBuildTinyUrlForPidSetsSiteToNullIfPidHasNoSite(): void
    {
        $this->extensionConfigurationMock->expects($this->once())
            ->method('setSite')
            ->with(null);

        $this->extensionConfigurationMock->expects($this->once())
            ->method('reset');

        $this->siteFinderMock->expects($this->once())
            ->method('getSiteByPageId')
            ->with(123)
            ->willThrowException(new SiteNotFoundException());

        $this->urlUtils->buildTinyUrlForPid('thekey', 123);
    }

    public function testCreateSpeakingTinyUrlReplacesMultipleNormalizedParamsMarkers(): void
    {
        $normalizedParamsMock = $this->createMock(NormalizedParams::class);
        $normalizedParamsMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn('myenvvalue1');
        $normalizedParamsMock->expects($this->once())
            ->method('getHttpUserAgent')
            ->willReturn('myenvvalue2');

        $this->extensionConfigurationMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###REMOTE_ADDR###/###HTTP_USER_AGENT###');
        $this->generalUtilityMock->expects($this->exactly(2))
            ->method('getNormalizedParams')
            ->willReturn($normalizedParamsMock);
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertSame('myenvvalue1/myenvvalue2', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlReplacesNormalizedParamsMarker(): void
    {
        $normalizedParamsMock = $this->createMock(NormalizedParams::class);
        $normalizedParamsMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn('replacedvalue');

        $this->extensionConfigurationMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###REMOTE_ADDR###');
        $this->generalUtilityMock->expects($this->once())
            ->method('getNormalizedParams')
            ->willReturn($normalizedParamsMock);
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertSame('replacedvalue', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlReplacesTinyUrlMarker(): void
    {
        $this->extensionConfigurationMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###TINY_URL_KEY###');
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertSame('testkey', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlReplacesUnknownMarkerWithEmptyString(): void
    {
        $this->extensionConfigurationMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###UNKNOWN_MARKER###');
        $this->generalUtilityMock->expects($this->once())
            ->method('getNormalizedParams')
            ->willReturn($this->createMock(NormalizedParams::class));
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertSame('', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlUsesBaseUrlForSiteUrlPlaceholder(): void
    {
        $this->extensionConfigurationMock
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###TYPO3_SITE_URL###/###TINY_URL_KEY###');

        $this->extensionConfigurationMock->expects($this->once())
            ->method('areSpeakingUrlsEnabled')
            ->willReturn(true);

        $this->extensionConfigurationMock->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn(new Uri('http://base.url.from.config'));

        $this->assertSame('http://base.url.from.config/thekey', $this->urlUtils->buildTinyUrl('thekey'));
    }

    public function testGenerateTinyurlHashCreatesHash(): void
    {
        /** @noinspection PhpDeprecationInspection */
        $this->assertSame(
            'ee85c8ee5b024efa864c06a98ed613286d134aad',
            $this->urlUtils->generateTinyurlHash('http://the-url.tld'),
        );
    }

    public function testGenerateTinyurlKeyForUidGeneratesKey(): void
    {
        $this->urlKeyGeneratorMock->expects($this->once())
            ->method('generateTinyurlKeyForUid')
            ->with(132)
            ->willReturn('thekey');

        /** @noinspection PhpDeprecationInspection */
        $this->assertSame('thekey', $this->urlUtils->generateTinyurlKeyForUid(132));
    }

    public function testRegenerateUrlKeyUpdatesKey(): void
    {
        $tinyUrl = TinyUrl::createNew();

        $this->urlKeyGeneratorMock->expects($this->once())
            ->method('generateTinyurlKeyForTinyUrl')
            ->with($tinyUrl)
            ->willReturn('thekey');

        $this->urlUtils->regenerateUrlKey($tinyUrl);

        /** @noinspection PhpDeprecationInspection */
        $this->assertSame('thekey', $tinyUrl->getUrlkey());
    }
}
