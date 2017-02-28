<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\UrlKeyGenerator;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\UrlKeyGenerator\Base62UrlKeyGenerator;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;

/**
 * Tests for the tinyurls API.
 */
class Base62UrlKeyGeneratorTest extends TestCase
{
    /**
     * @var Base62UrlKeyGenerator
     */
    protected $base62UrlKeyGenerator;

    /**
     * @var ExtensionConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $extensionConfigurationMock;

    /**
     * @var GeneralUtilityWrapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $generalUtilityMock;

    protected function setUp()
    {
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->generalUtilityMock = $this->createMock(GeneralUtilityWrapper::class);

        $this->base62UrlKeyGenerator = new Base62UrlKeyGenerator();
        $this->base62UrlKeyGenerator->injectExtensionConfiguration($this->extensionConfigurationMock);
        $this->base62UrlKeyGenerator->injectGeneralUtility($this->generalUtilityMock);
    }

    public function testGenerateTinyurlKeyForUidEncodesIntegerIfNoMinimalLengthIsConfigured()
    {
        $this->extensionConfigurationMock->method('getBase62Dictionary')
            ->willReturn('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(1243);
        $key = $this->base62UrlKeyGenerator->generateTinyurlKeyForTinyUrl($tinyUrl);
        $this->assertEquals('ud', $key);
    }

    public function testGenerateTinyurlKeyForUidFillsUpKeyUpToConfiguredMinimalLength()
    {
        $this->extensionConfigurationMock->method('getBase62Dictionary')
            ->willReturn('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $this->extensionConfigurationMock->method('getMinimalTinyurlKeyLength')
            ->willReturn(4);

        $this->generalUtilityMock->method('getRandomHexString')
            ->with(2)
            ->willReturn('ag');

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(1243);
        $key = $this->base62UrlKeyGenerator->generateTinyurlKeyForTinyUrl($tinyUrl);
        $this->assertEquals('ud-ag', $key);
    }

    public function testGenerateTinyurlKeyForUidFillsUpKeyWithConfiguredMinimalRandomPart()
    {
        $this->extensionConfigurationMock->method('getBase62Dictionary')
            ->willReturn('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $this->extensionConfigurationMock->method('getMinimalTinyurlKeyLength')
            ->willReturn(3);

        $this->extensionConfigurationMock->method('getMinimalRandomKeyLength')
            ->willReturn(2);

        $this->generalUtilityMock->method('getRandomHexString')
            ->with(2)
            ->willReturn('ag');

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(1243);
        $key = $this->base62UrlKeyGenerator->generateTinyurlKeyForTinyUrl($tinyUrl);
        $this->assertEquals('ud-ag', $key);
    }
}
