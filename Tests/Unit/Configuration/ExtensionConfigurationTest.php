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
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration as TYPO3ExtensionConfiguration;

/**
 * @backupGlobals enabled
 */
class ExtensionConfigurationTest extends TestCase
{
    private ExtensionConfiguration $extensionConfiguration;

    private TYPO3ExtensionConfiguration|MockObject $typo3ExtensionConfigurationMock;

    protected function setUp(): void
    {
        $this->typo3ExtensionConfigurationMock = $this->createMock(TYPO3ExtensionConfiguration::class);

        $this->extensionConfiguration = new ExtensionConfiguration($this->typo3ExtensionConfigurationMock);
    }

    public function testAppendPidQueryAppendsAndStatementForNonEmptyQuery(): void
    {
        $this->initConfig(['urlRecordStoragePID' => 0]);
        self::assertSame('a=1 AND pid=0', $this->extensionConfiguration->appendPidQuery('a=1'));
    }

    public function testAppendPidQueryAppendsConfiguredPid(): void
    {
        $this->initConfig([]);
        self::assertSame('pid=0', $this->extensionConfiguration->appendPidQuery(''));
    }

    public function testAppendPidQueryAppendsDefaultPid(): void
    {
        $this->initConfig(['urlRecordStoragePID' => 999]);
        self::assertSame('pid=999', $this->extensionConfiguration->appendPidQuery(''));
    }

    public function testAreSpeakingUrlsEnabledReturnsFalseByDefault(): void
    {
        $this->initConfig([]);
        self::assertFalse($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testAreSpeakingUrlsEnabledReturnsFalseIfConfigured(): void
    {
        $this->initConfig(['createSpeakingURLs' => 0]);
        self::assertFalse($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testAreSpeakingUrlsEnabledReturnsTrueIfConfigured(): void
    {
        $this->initConfig(['createSpeakingURLs' => 1]);
        self::assertTrue($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testGetBase62DictionaryReturnsConfiguredValue(): void
    {
        $this->initConfig(['base62Dictionary' => 'asfduew']);
        self::assertSame('asfduew', $this->extensionConfiguration->getBase62Dictionary());
    }

    public function testGetBase62DictionaryReturnsDefault(): void
    {
        $this->initConfig([]);

        self::assertSame(
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            $this->extensionConfiguration->getBase62Dictionary()
        );
    }

    public function testGetMinimalRandomKeyLengthReturnsConfiguredValue(): void
    {
        $this->initConfig(['minimalRandomKeyLength' => 56]);
        self::assertSame(56, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetMinimalRandomKeyLengthReturnsDefault(): void
    {
        $this->initConfig([]);
        self::assertSame(2, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetMinimalTinyurlKeyLengthReturnsConfiguredValue(): void
    {
        $this->initConfig(['minimalTinyurlKeyLength' => 75]);
        self::assertSame(75, $this->extensionConfiguration->getMinimalTinyurlKeyLength());
    }

    public function testGetMinimalTinyurlKeyLengthReturnsDefault(): void
    {
        $this->initConfig([]);
        self::assertSame(2, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetSpeakingUrlTemplateReturnsConfiguredValue(): void
    {
        $this->initConfig(['speakingUrlTemplate' => 'koaidp']);
        self::assertSame('koaidp', $this->extensionConfiguration->getSpeakingUrlTemplate());
    }

    public function testGetSpeakingUrlTemplateReturnsDefault(): void
    {
        $this->initConfig([]);

        self::assertSame(
            '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###',
            $this->extensionConfiguration->getSpeakingUrlTemplate()
        );
    }

    private function initConfig(array $array): void
    {
        $this->typo3ExtensionConfigurationMock->expects(self::once())
            ->method('get')
            ->with('tinyurls')
            ->willReturn($array);
    }
}
