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
        $this->generalUtilityMock->expects(self::once())
            ->method('getIndpEnv')
            ->with('TYPO3_SITE_URL')
            ->willReturn('http://the-site.url/');

        $this->extensionConfigurationMock->expects(self::once())
            ->method('areSpeakingUrlsEnabled')
            ->willReturn(false);

        self::assertSame(
            'http://the-site.url/?eID=tx_tinyurls&tx_tinyurls[key]=thekey',
            $this->urlUtils->buildTinyUrl('thekey'),
        );
    }

    public function testBuildTinyUrlCreatesSpeakingUrlIfEnabled(): void
    {
        $this->extensionConfigurationMock
            ->method('getSpeakingUrlTemplate')
            ->willReturn('http://base.url/###TINY_URL_KEY###');

        $this->extensionConfigurationMock->expects(self::once())
            ->method('areSpeakingUrlsEnabled')
            ->willReturn(true);

        self::assertSame('http://base.url/thekey', $this->urlUtils->buildTinyUrl('thekey'));
    }

    public function testCreateSpeakingTinyUrlReplacesIndependentEnvironmentMarker(): void
    {
        $this->extensionConfigurationMock->expects(self::once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###MY_ENV_MARKER###');
        $this->generalUtilityMock->expects(self::once())
            ->method('getIndpEnv')
            ->willReturn('replacedvalue');
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        self::assertSame('replacedvalue', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlReplacesMultipleIndependentEnvironmentMarkers(): void
    {
        $this->extensionConfigurationMock->expects(self::once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###MY_ENV_MARKER1###/###MY_ENV_MARKER2###');
        $this->generalUtilityMock->expects(self::exactly(2))
            ->method('getIndpEnv')
            ->willReturnOnConsecutiveCalls('myenvvalue1', 'myenvvalue2');
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        self::assertSame('myenvvalue1/myenvvalue2', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlReplacesTinyUrlMarker(): void
    {
        $this->extensionConfigurationMock->expects(self::once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###TINY_URL_KEY###');
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        self::assertSame('testkey', $speakingUrl);
    }

    public function testGenerateTinyurlHashCreatesHash(): void
    {
        /** @noinspection PhpDeprecationInspection */
        self::assertSame(
            'ee85c8ee5b024efa864c06a98ed613286d134aad',
            $this->urlUtils->generateTinyurlHash('http://the-url.tld'),
        );
    }

    public function testGenerateTinyurlKeyForUidGeneratesKey(): void
    {
        $this->urlKeyGeneratorMock->expects(self::once())
            ->method('generateTinyurlKeyForUid')
            ->with(132)
            ->willReturn('thekey');

        /** @noinspection PhpDeprecationInspection */
        self::assertSame('thekey', $this->urlUtils->generateTinyurlKeyForUid(132));
    }

    public function testRegenerateUrlKeyUpdatesKey(): void
    {
        $tinyUrl = TinyUrl::createNew();

        $this->urlKeyGeneratorMock->expects(self::once())
            ->method('generateTinyurlKeyForTinyUrl')
            ->with($tinyUrl)
            ->willReturn('thekey');

        $this->urlUtils->regenerateUrlKey($tinyUrl);

        /** @noinspection PhpDeprecationInspection */
        self::assertSame('thekey', $tinyUrl->getUrlkey());
    }
}
