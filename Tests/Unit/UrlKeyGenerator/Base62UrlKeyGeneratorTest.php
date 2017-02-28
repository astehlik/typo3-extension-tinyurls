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
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use Tx\Tinyurls\Utils\UrlUtils;

/**
 * Tests for the tinyurls API.
 */
class Base62UrlKeyGeneratorTest extends TestCase
{
    /**
     * @var ExtensionConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $extensionConfigurationMock;

    /**
     * @var GeneralUtilityWrapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $generalUtilityMock;

    /**
     * @var UrlUtils
     */
    protected $urlUtils;

    protected function setUp()
    {
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->generalUtilityMock = $this->createMock(GeneralUtilityWrapper::class);

        $this->urlUtils = new UrlUtils();
        $this->urlUtils->injectExtensionConfiguration($this->extensionConfigurationMock);
        $this->urlUtils->injectGeneralUtility($this->generalUtilityMock);
    }

    public function testGenerateTinyurlKeyForUidEncodesIntegerIfNoMinimalLengthIsConfigured()
    {
        $this->extensionConfigurationMock->method('getBase62Dictionary')
            ->willReturn('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $key = $this->urlUtils->generateTinyurlKeyForUid(1243);
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

        $key = $this->urlUtils->generateTinyurlKeyForUid(1243);
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

        $key = $this->urlUtils->generateTinyurlKeyForUid(1243);
        $this->assertEquals('ud-ag', $key);
    }
}
