<?php
declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Hooks;

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
use Tx\Tinyurls\Hooks\TypoLink;
use Tx\Tinyurls\TinyUrl\Api;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TypoLinkTest extends TestCase
{
    /**
     * @var array
     */
    protected $finalTag;

    /**
     * @var array
     */
    protected $finalTagOriginal;

    /**
     * @var array
     */
    protected $finalTagParts;

    /**
     * @var array
     */
    protected $finalTagPartsOriginal;

    /**
     * @var string
     */
    protected $lastTypoLinkUrlOriginal;

    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tinyUrlApiMock;

    /**
     * @var array
     */
    protected $typoLinkConfig;

    /**
     * @var array
     */
    protected $typoLinkConfigOriginal;

    /**
     * @var TypoLink
     */
    protected $typoLinkHook;

    protected function setUp()
    {
        $this->tinyUrlApiMock = $this->createMock(Api::class);
        $this->typoLinkHook = new TypoLink();
        $this->typoLinkHook->setTinyUrlApi($this->tinyUrlApiMock);
    }

    public function testApiGetTinyUrlIsCalledWithOriginalUrl()
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->expects($this->once())
            ->method('getTinyUrl')
            ->with($this->lastTypoLinkUrlOriginal)
            ->willReturn('the tiny url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);
    }

    public function testApiIsInitializedWithLinkConfig()
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('the url');

        $this->tinyUrlApiMock->expects($this->once())
            ->method('initializeConfigFromTyposcript')
            ->with($this->typoLinkConfigOriginal, $contentObjectRendererMock);

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);
    }

    public function testContentObjectRendererLastTypoLinkUrlIsSetToTinyUrl()
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('http://the-tiny-url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);

        $this->assertEquals('http://the-tiny-url', $contentObjectRendererMock->lastTypoLinkUrl);
    }

    public function testFinalTagPartsUrlIsReplacedWithTinyUrl()
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('http://the-tiny-url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);

        $this->assertEquals('http://the-tiny-url', $this->finalTagParts['url']);
    }

    public function testFinalTagUrlIsReplacedWithTinyUrl()
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('http://the-tiny-url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);

        $this->assertEquals('<a href="http://the-tiny-url">http://the-tiny-url</a>', $this->finalTag);
    }

    public function testSkipsProcessingForMailtoUrl()
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameterArray = $this->getTypoLinkParameterArray('mailto');
        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameterArray, $contentObjectRendererMock);
        $this->assertLinkUnchanged($contentObjectRendererMock);
    }

    public function testSkipsProcessingIfDisabled()
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameterArray = $this->getTypoLinkParameterArray('page', false);
        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameterArray, $contentObjectRendererMock);
        $this->assertLinkUnchanged($contentObjectRendererMock);
    }

    protected function assertLinkUnchanged(ContentObjectRenderer $contentObjectRendererMock)
    {
        $this->assertEquals($this->lastTypoLinkUrlOriginal, $contentObjectRendererMock->lastTypoLinkUrl);
        $this->assertEquals($this->finalTagOriginal, $this->finalTag);
        $this->assertEquals($this->finalTagPartsOriginal, $this->finalTagParts);
        $this->assertEquals($this->typoLinkConfigOriginal, $this->typoLinkConfig);
    }

    /**
     * @return ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentObjectRendererMock()
    {
        $this->lastTypoLinkUrlOriginal = 'http://the-original.tld';
        /** @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject $contentObjectRendererMock */
        $contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);
        $contentObjectRendererMock->lastTypoLinkUrl = $this->lastTypoLinkUrlOriginal;
        return $contentObjectRendererMock;
    }

    protected function getTypoLinkParameterArray(string $typoLinkType = 'page', $tinyUrlEnabled = true)
    {
        $linkText = '';
        $linkDetails = [];

        $this->typoLinkConfig = [
            'tinyurl' => $tinyUrlEnabled,
            'someother' => 'config',
        ];
        $this->typoLinkConfigOriginal = $this->typoLinkConfig;

        $this->finalTag = '<a href="' . $this->lastTypoLinkUrlOriginal . '">' . $this->lastTypoLinkUrlOriginal . '</a>';
        $this->finalTagOriginal = $this->finalTag;

        $this->finalTagParts = [
            'TYPE' => $typoLinkType,
            'url' => $this->lastTypoLinkUrlOriginal,
        ];
        $this->finalTagPartsOriginal = $this->finalTagParts;

        return [
            'conf' => &$this->typoLinkConfig,
            'linktxt' => $linkText,
            'finalTag' => &$this->finalTag,
            'finalTagParts' => &$this->finalTagParts,
            'linkDetails' => $linkDetails,
        ];
    }
}
