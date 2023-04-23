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

use PHPUnit\Framework\MockObject\MockObject;
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
     * @var Api|MockObject
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

    protected function setUp(): void
    {
        $this->tinyUrlApiMock = $this->createMock(Api::class);
        $this->typoLinkHook = new TypoLink();
        $this->typoLinkHook->setTinyUrlApi($this->tinyUrlApiMock);
    }

    public function testApiGetTinyUrlIsCalledWithOriginalUrl(): void
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->expects(self::once())
            ->method('getTinyUrl')
            ->with($this->lastTypoLinkUrlOriginal)
            ->willReturn('the tiny url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);
    }

    public function testApiIsInitializedWithLinkConfig(): void
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('the url');

        $this->tinyUrlApiMock->expects(self::once())
            ->method('initializeConfigFromTyposcript')
            ->with($this->typoLinkConfigOriginal, $contentObjectRendererMock);

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);
    }

    public function testContentObjectRendererLastTypoLinkUrlIsSetToTinyUrl(): void
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('http://the-tiny-url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);

        self::assertSame('http://the-tiny-url', $contentObjectRendererMock->lastTypoLinkUrl);
    }

    public function testFinalTagPartsUrlIsReplacedWithTinyUrl(): void
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('http://the-tiny-url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);

        self::assertSame('http://the-tiny-url', $this->finalTagParts['url']);
    }

    public function testFinalTagUrlIsReplacedWithTinyUrl(): void
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameters = $this->getTypoLinkParameterArray();

        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('http://the-tiny-url');

        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameters, $contentObjectRendererMock);

        self::assertSame('<a href="http://the-tiny-url">http://the-tiny-url</a>', $this->finalTag);
    }

    public function testSkipsProcessingForMailtoUrl(): void
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameterArray = $this->getTypoLinkParameterArray('mailto');
        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameterArray, $contentObjectRendererMock);
        $this->assertLinkUnchanged($contentObjectRendererMock);
    }

    public function testSkipsProcessingIfDisabled(): void
    {
        $contentObjectRendererMock = $this->getContentObjectRendererMock();
        $typoLinkParameterArray = $this->getTypoLinkParameterArray('page', false);
        $this->typoLinkHook->convertTypolinkToTinyUrl($typoLinkParameterArray, $contentObjectRendererMock);
        $this->assertLinkUnchanged($contentObjectRendererMock);
    }

    protected function assertLinkUnchanged(ContentObjectRenderer $contentObjectRendererMock): void
    {
        self::assertSame($this->lastTypoLinkUrlOriginal, $contentObjectRendererMock->lastTypoLinkUrl);
        self::assertSame($this->finalTagOriginal, $this->finalTag);
        self::assertSame($this->finalTagPartsOriginal, $this->finalTagParts);
        self::assertSame($this->typoLinkConfigOriginal, $this->typoLinkConfig);
    }

    /**
     * @return ContentObjectRenderer|MockObject
     */
    protected function getContentObjectRendererMock()
    {
        $this->lastTypoLinkUrlOriginal = 'http://the-original.tld';

        /** @var ContentObjectRenderer|MockObject $contentObjectRendererMock */
        $contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);
        $contentObjectRendererMock->lastTypoLinkUrl = $this->lastTypoLinkUrlOriginal;
        return $contentObjectRendererMock;
    }

    protected function getTypoLinkParameterArray(string $typoLinkType = 'page', $tinyUrlEnabled = true): array
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
