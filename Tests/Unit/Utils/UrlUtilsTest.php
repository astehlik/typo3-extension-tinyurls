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
use Tx\Tinyurls\Utils\ConfigUtils;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use Tx\Tinyurls\Utils\UrlUtils;

/**
 * Tests for the tinyurls API.
 */
class UrlUtilsTest extends TestCase
{
    /**
     * @var ConfigUtils|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configUtilityMock;

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
        $this->configUtilityMock = $this->createMock(ConfigUtils::class);
        $this->generalUtilityMock = $this->createMock(GeneralUtilityWrapper::class);

        $this->urlUtils = new UrlUtils();
        $this->urlUtils->injectConfigUtils($this->configUtilityMock);
        $this->urlUtils->injectGeneralUtility($this->generalUtilityMock);
    }

    public function testCreateSpeakingTinyUrlReplacesIndependentEnvironmentMarker()
    {
        $this->configUtilityMock->expects($this->once())
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
        $this->configUtilityMock->expects($this->once())
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
        $this->configUtilityMock->expects($this->once())
            ->method('getSpeakingUrlTemplate')
            ->willReturn('###TINY_URL_KEY###');
        $speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
        $this->assertEquals('testkey', $speakingUrl);
    }

    public function testGenerateTinyurlKeyForUidEncodesIntegerIfNoMinimalLengthIsConfigured()
    {
        $this->configUtilityMock->method('getBase62Dictionary')
            ->willReturn('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $key = $this->urlUtils->generateTinyurlKeyForUid(1243);
        $this->assertEquals('ud', $key);
    }

    public function testGenerateTinyurlKeyForUidFillsUpKeyUpToConfiguredMinimalLength()
    {
        $this->configUtilityMock->method('getBase62Dictionary')
            ->willReturn('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $this->configUtilityMock->method('getMinimalTinyurlKeyLength')
            ->willReturn(4);

        $this->generalUtilityMock->method('getRandomHexString')
            ->with(2)
            ->willReturn('ag');

        $key = $this->urlUtils->generateTinyurlKeyForUid(1243);
        $this->assertEquals('ud-ag', $key);
    }

    public function testGenerateTinyurlKeyForUidFillsUpKeyWithConfiguredMinimalRandomPart()
    {
        $this->configUtilityMock->method('getBase62Dictionary')
            ->willReturn('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $this->configUtilityMock->method('getMinimalTinyurlKeyLength')
            ->willReturn(3);

        $this->configUtilityMock->method('getMinimalRandomKeyLength')
            ->willReturn(2);

        $this->generalUtilityMock->method('getRandomHexString')
            ->with(2)
            ->willReturn('ag');

        $key = $this->urlUtils->generateTinyurlKeyForUid(1243);
        $this->assertEquals('ud-ag', $key);
    }
}
