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
use Tx\Tinyurls\Configuration\TypoScriptConfigurator;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * @backupGlobals enabled
 */
class ExtensionConfigurationTest extends TestCase
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls'] = [];
        $this->extensionConfiguration = new ExtensionConfiguration();
    }

    public function testAppendPidQueryAppendsAndStatementForNonEmptyQuery(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['urlRecordStoragePID'] = 0;
        self::assertSame('a=1 AND pid=0', $this->extensionConfiguration->appendPidQuery('a=1'));
    }

    public function testAppendPidQueryAppendsConfiguredPid(): void
    {
        self::assertSame('pid=0', $this->extensionConfiguration->appendPidQuery(''));
    }

    public function testAppendPidQueryAppendsDefaultPid(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['urlRecordStoragePID'] = 999;
        self::assertSame('pid=999', $this->extensionConfiguration->appendPidQuery(''));
    }

    public function testAreSpeakingUrlsEnabledReturnsFalseByDefault(): void
    {
        self::assertFalse($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testAreSpeakingUrlsEnabledReturnsFalseIfConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['createSpeakingURLs'] = 0;
        self::assertFalse($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testAreSpeakingUrlsEnabledReturnsTrueIfConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['createSpeakingURLs'] = 1;
        self::assertTrue($this->extensionConfiguration->areSpeakingUrlsEnabled());
    }

    public function testGetBase62DictionaryReturnsConfiguredValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['base62Dictionary'] = 'asfduew';
        self::assertSame('asfduew', $this->extensionConfiguration->getBase62Dictionary());
    }

    public function testGetBase62DictionaryReturnsDefault(): void
    {
        self::assertSame(
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            $this->extensionConfiguration->getBase62Dictionary()
        );
    }

    public function testGetExtensionConfigurationReturnsExtensionConfiguration(): void
    {
        /** @noinspection PhpDeprecationInspection */
        self::assertArrayHasKey('createSpeakingURLs', $this->extensionConfiguration->getExtensionConfiguration());
    }

    public function testGetExtensionConfigurationValueThrowsExceptionForNonExistingKey(): void
    {
        $this->expectExceptionMessage('The key a non existing key does not exists in the extension configuration');

        /** @noinspection PhpDeprecationInspection */
        $this->extensionConfiguration->getExtensionConfigurationValue('a non existing key');
    }

    public function testGetMinimalRandomKeyLengthReturnsConfiguredValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['base62Dictionary'] = 'asdf';
        self::assertSame('asdf', $this->extensionConfiguration->getBase62Dictionary());
    }

    public function testGetMinimalRandomKeyLengthReturnsDefault(): void
    {
        self::assertSame(2, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetMinimalTinyurlKeyLengthReturnsConfiguredValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['minimalRandomKeyLength'] = '31';
        self::assertSame(31, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetMinimalTinyurlKeyLengthReturnsDefault(): void
    {
        self::assertSame(2, $this->extensionConfiguration->getMinimalRandomKeyLength());
    }

    public function testGetSpeakingUrlTemplateReturnsConfiguredValue(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls']['speakingUrlTemplate'] = 'koaidp';
        self::assertSame('koaidp', $this->extensionConfiguration->getSpeakingUrlTemplate());
    }

    public function testGetSpeakingUrlTemplateReturnsDefault(): void
    {
        self::assertSame(
            '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###',
            $this->extensionConfiguration->getSpeakingUrlTemplate()
        );
    }

    public function testInitializeConfigFromTyposcriptUsesTypoScriptConfiguratorForInitalizingConfig(): void
    {
        /** @var ContentObjectRenderer|MockObject $contentObjectRendererMock */
        $contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);

        /** @var MockObject|TinyUrlGenerator $tinyUrlGeneratorMock */
        $tinyUrlGeneratorMock = $this->createMock(TinyUrlGenerator::class);

        /** @var MockObject|TypoScriptConfigurator $typoScriptConfiguratorMock */
        $typoScriptConfiguratorMock = $this->createMock(TypoScriptConfigurator::class);

        $typoScriptConfiguratorMock->expects(self::once())
            ->method('initializeConfigFromTyposcript')
            ->with(['the' => 'config'], $contentObjectRendererMock);

        $this->extensionConfiguration->setTypoScriptConfigurator($typoScriptConfiguratorMock);
        $this->extensionConfiguration->initializeConfigFromTyposcript(
            ['the' => 'config'],
            $contentObjectRendererMock,
            $tinyUrlGeneratorMock
        );
    }
}
