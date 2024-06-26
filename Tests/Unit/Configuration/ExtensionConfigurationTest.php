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

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Configuration\ConfigKeys;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Configuration\SiteConfigurationInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration as TYPO3ExtensionConfiguration;
use TYPO3\CMS\Core\Site\Entity\Site;

#[BackupGlobals(true)]
class ExtensionConfigurationTest extends TestCase
{
    private ExtensionConfiguration $extensionConfiguration;

    private MockObject|SiteConfigurationInterface $siteConfigurationMock;

    private MockObject|TYPO3ExtensionConfiguration $typo3ExtensionConfigurationMock;

    protected function setUp(): void
    {
        $this->siteConfigurationMock = $this->createMock(SiteConfigurationInterface::class);
        $this->typo3ExtensionConfigurationMock = $this->createMock(TYPO3ExtensionConfiguration::class);

        $this->extensionConfiguration = new ExtensionConfiguration(
            $this->siteConfigurationMock,
            $this->typo3ExtensionConfigurationMock,
        );
    }

    public function testAppendPidQueryAppendsAndStatementForNonEmptyQuery(): void
    {
        $this->initConfig([ConfigKeys::URL_RECORD_STORAGE_PID => 0]);
        self::assertSame('a=1 AND pid=0', $this->extensionConfiguration->appendPidQuery('a=1'));
    }

    public function testAppendPidQueryAppendsConfiguredPid(): void
    {
        $this->initConfig([]);
        self::assertSame('pid=0', $this->extensionConfiguration->appendPidQuery(''));
    }

    public function testAppendPidQueryAppendsDefaultPid(): void
    {
        $this->initConfig([ConfigKeys::URL_RECORD_STORAGE_PID => 999]);
        self::assertSame('pid=999', $this->extensionConfiguration->appendPidQuery(''));
    }

    public function testAreSpeakingUrlsEnabledReturnsFalseByDefault(): void
    {
        $this->initConfig([]);
        self::assertFalse($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testAreSpeakingUrlsEnabledReturnsFalseIfConfigured(): void
    {
        $this->initConfig([ConfigKeys::CREATE_SPEAKING_URLS => 0]);
        self::assertFalse($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testAreSpeakingUrlsEnabledReturnsTrueIfConfigured(): void
    {
        $this->initConfig([ConfigKeys::CREATE_SPEAKING_URLS => 1]);
        self::assertTrue($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testGetBase62DictionaryReturnsConfiguredValue(): void
    {
        $this->initConfig([ConfigKeys::BASE62_DICTIONARY => 'asfduew']);
        self::assertSame('asfduew', $this->extensionConfiguration->getBase62Dictionary());
    }

    public function testGetBase62DictionaryReturnsDefault(): void
    {
        $this->initConfig([]);

        self::assertSame(
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            $this->extensionConfiguration->getBase62Dictionary(),
        );
    }

    public function testGetBaseUrlResturnsSiteBaseIfConfigured(): void
    {
        $this->initConfig([ConfigKeys::BASE_URL_FROM_SITE_BASE => 1]);

        $siteMock = $this->createMock(Site::class);
        $siteMock->expects(self::once())
            ->method('getBase')
            ->willReturn(new Uri('https://base.url.from.site'));

        $this->extensionConfiguration->setSite($siteMock);

        // @extensionScannerIgnoreLine
        self::assertSame('https://base.url.from.site', (string)$this->extensionConfiguration->getBaseUrl());
    }

    public function testGetBaseUrlReturnsNullByDefault(): void
    {
        $this->initConfig([]);

        // @extensionScannerIgnoreLine
        self::assertNull($this->extensionConfiguration->getBaseUrl());
    }

    public function testGetMinimalRandomKeyLengthReturnsConfiguredValue(): void
    {
        $this->initConfig([ConfigKeys::MINIMAL_RANDOM_KEY_LENGTH => 56]);
        self::assertSame(56, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetMinimalRandomKeyLengthReturnsDefault(): void
    {
        $this->initConfig([]);
        self::assertSame(2, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetMinimalTinyurlKeyLengthReturnsConfiguredValue(): void
    {
        $this->initConfig([ConfigKeys::MINIMAL_TINYURL_KEY_LENGTH => 75]);
        self::assertSame(75, $this->extensionConfiguration->getMinimalTinyurlKeyLength());
    }

    public function testGetMinimalTinyurlKeyLengthReturnsDefault(): void
    {
        $this->initConfig([]);
        self::assertSame(2, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetSpeakingUrlTemplateReturnsConfiguredValue(): void
    {
        $this->initConfig([ConfigKeys::SPEAKING_URL_TEMPLATE => 'koaidp']);
        self::assertSame('koaidp', $this->extensionConfiguration->getSpeakingUrlTemplate());
    }

    public function testGetSpeakingUrlTemplateReturnsDefault(): void
    {
        $this->initConfig([]);

        self::assertSame(
            '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###',
            $this->extensionConfiguration->getSpeakingUrlTemplate(),
        );
    }

    public function testSiteConfigurationIsMergedIntoExtensionConfiguration(): void
    {
        $this->initConfig([ConfigKeys::BASE_URL => 'https://base.url.from.extension']);

        $this->siteConfigurationMock->method('loadSiteConfiguration')
            ->willReturn([ConfigKeys::BASE_URL => 'https://base.url.from.site']);

        // @extensionScannerIgnoreLine
        self::assertSame('https://base.url.from.site', (string)$this->extensionConfiguration->getBaseUrl());
    }

    private function initConfig(array $array): void
    {
        $this->typo3ExtensionConfigurationMock->expects(self::once())
            ->method('get')
            ->with('tinyurls')
            ->willReturn($array);
    }
}
