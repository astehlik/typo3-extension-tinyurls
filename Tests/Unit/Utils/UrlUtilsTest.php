<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Utils;

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
use Tx\Tinyurls\Object\ImplementationManager;
use Tx\Tinyurls\UrlKeyGenerator\Base62UrlKeyGenerator;
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use Tx\Tinyurls\Utils\UrlUtils;

/**
 * Tests for the tinyurls API.
 */
class UrlUtilsTest extends TestCase
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

    /**
     * @backupGlobals enabled
     */
    public function testBuildTinyUrlCreatesEidUrlIfSpeakingUrlsAreDisabled()
    {
        $this->generalUtilityMock->expects($this->once())
            ->method('getIndpEnv')
            ->with('TYPO3_SITE_URL')
            ->willReturn('http://the-site.url/');

        $this->extensionConfigurationMock->expects($this->once())
            ->method('areSpeakingUrlsEnabled')
            ->willReturn(false);

        $this->assertEquals(
            'http://the-site.url/?eID=tx_tinyurls&tx_tinyurls[key]=thekey',
            $this->urlUtils->buildTinyUrl('thekey')
        );
    }

    public function testBuildTinyUrlCreatesSpeakingUrlIfEnabled()
    {
        $this->extensionConfigurationMock
            ->method('getSpeakingUrlTemplate')
            ->willReturn('http://base.url/###TINY_URL_KEY###');

        $this->extensionConfigurationMock->expects($this->once())
            ->method('areSpeakingUrlsEnabled')
            ->willReturn(true);

        $this->assertEquals('http://base.url/thekey', $this->urlUtils->buildTinyUrl('thekey'));
    }

    public function testCreateSpeakingTinyUrlReplacesIndependentEnvironmentMarker()
    {
        $this->extensionConfigurationMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###MY_ENV_MARKER###');
        $this->generalUtilityMock->expects($this->once())
            ->method('getIndpEnv')
            ->willReturn('replacedvalue');
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertEquals('replacedvalue', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlReplacesMultipleIndependentEnvironmentMarkers()
    {
        $this->extensionConfigurationMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###MY_ENV_MARKER1###/###MY_ENV_MARKER2###');
        $this->generalUtilityMock->expects($this->exactly(2))
            ->method('getIndpEnv')
            ->will($this->onConsecutiveCalls('myenvvalue1', 'myenvvalue2'));
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertEquals('myenvvalue1/myenvvalue2', $speakingUrl);
    }

    public function testCreateSpeakingTinyUrlReplacesTinyUrlMarker()
    {
        $this->extensionConfigurationMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###TINY_URL_KEY###');
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertEquals('testkey', $speakingUrl);
    }

    public function testGenerateTinyurlHashCreatesHash()
    {
        $this->assertEquals(
            'ee85c8ee5b024efa864c06a98ed613286d134aad',
            $this->urlUtils->generateTinyurlHash('http://the-url.tld')
        );
    }

    public function testGenerateTinyurlKeyForUidGeneratesKey()
    {
        $urlGeneratorMock = $this->createMock(UrlKeyGenerator::class);
        $urlGeneratorMock->expects($this->once())
            ->method('generateTinyurlKeyForUid')
            ->with(132)
            ->willReturn('thekey');
        ImplementationManager::getInstance()->setUrlKeyGenerator($urlGeneratorMock);
        $this->assertEquals('thekey', $this->urlUtils->generateTinyurlKeyForUid(132));
        ImplementationManager::getInstance()->restoreDefaults();
    }
}
