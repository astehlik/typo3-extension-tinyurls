<?php
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

use PHPUnit_Framework_TestCase as PHPUnitTestCase;
use Tx\Tinyurls\Utils\ConfigUtils;
use Tx\Tinyurls\Utils\UrlUtils;

/**
 * Tests for the tinyurls API.
 */
class UrlUtilsTest extends PHPUnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $configUtilityMock;

	/**
	 * @var UrlUtils|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $urlUtils;

	/**
	 *
	 */
	public function setUp() {
		$this->configUtilityMock = $this->getMock(ConfigUtils::class);
		$this->urlUtils = $this->getMockBuilder(UrlUtils::class)
			->setMethods(array('getIndependentEnvironmentVariable', 'getConfigUtils'))
			->disableOriginalConstructor()
			->getMock();
		$this->urlUtils->expects($this->once())->method('getConfigUtils')->will($this->returnValue($this->configUtilityMock));
		$this->urlUtils->__construct();
	}

	/**
	 * @test
	 * @covers \Tx\Tinyurls\TinyUrl\TinyUrlGenerator::createSpeakingTinyUrl
	 */
	public function createSpeakingTinyUrlReplacesIndependentEnvironmentMarker() {
		$this->configUtilityMock->expects($this->once())->method('getExtensionConfigurationValue')->will($this->returnValue('###MY_ENV_MARKER###'));
		$this->urlUtils->expects($this->once())->method('getIndependentEnvironmentVariable')->will($this->returnValue('replacedvalue'));
		$speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
		$this->assertEquals('replacedvalue', $speakingUrl);
	}

	/**
	 * @test
	 * @covers \Tx\Tinyurls\TinyUrl\TinyUrlGenerator::createSpeakingTinyUrl
	 */
	public function createSpeakingTinyUrlReplacesMultipleIndependentEnvironmentMarkers() {
		$this->configUtilityMock->expects($this->once())->method('getExtensionConfigurationValue')->will($this->returnValue('###MY_ENV_MARKER1###/###MY_ENV_MARKER2###'));
		$this->urlUtils->expects($this->exactly(2))->method('getIndependentEnvironmentVariable')->will($this->onConsecutiveCalls('myenvvalue1', 'myenvvalue2'));
		$speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
		$this->assertEquals('myenvvalue1/myenvvalue2', $speakingUrl);
	}

	/**
	 * @test
	 * @covers \Tx\Tinyurls\TinyUrl\TinyUrlGenerator::createSpeakingTinyUrl
	 */
	public function createSpeakingTinyUrlReplacesTinyUrlMarker() {
		$this->configUtilityMock->expects($this->once())->method('getExtensionConfigurationValue')->will($this->returnValue('###TINY_URL_KEY###'));
		$speakingUrl = $this->urlUtils->createSpeakingTinyUrl('testkey');
		$this->assertEquals('testkey', $speakingUrl);
	}
}
