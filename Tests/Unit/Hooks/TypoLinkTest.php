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
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;
use TYPO3\CMS\Frontend\Typolink\LinkResult;

class TypoLinkTest extends TestCase
{
    private const TARGET_URL = 'https://the-tiny-url.tld';

    private ContentObjectRenderer|MockObject $contentObjectRendererMock;

    private array $linkInstructions;

    private Api|MockObject $tinyUrlApiMock;

    private TypoLink $typoLinkHook;

    protected function setUp(): void
    {
        $this->tinyUrlApiMock = $this->createMock(Api::class);
        $this->contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);

        $this->typoLinkHook = new TypoLink($this->tinyUrlApiMock);
    }

    public function testApiGetTinyUrlIsCalledWithOriginalUrl(): void
    {
        $this->tinyUrlApiMock->expects(self::once())
            ->method('getTinyUrl')
            ->with(self::TARGET_URL)
            ->willReturn('the tiny url');

        $this->typoLinkHook->__invoke($this->getAfterLinkCreatedEvent());
    }

    public function testApiIsInitializedWithLinkConfig(): void
    {
        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('the url');

        $event = $this->getAfterLinkCreatedEvent(tinyUrlConfig: ['the' => 'config']);

        $this->tinyUrlApiMock->expects(self::once())
            ->method('initializeConfigFromTyposcript')
            ->with($this->linkInstructions, $this->contentObjectRendererMock);

        $this->typoLinkHook->__invoke($event);
    }

    public function testSkipsProcessingForMailtoUrl(): void
    {
        $this->tinyUrlApiMock->expects(self::never())->method('getTinyUrl');

        $this->typoLinkHook->__invoke($this->getAfterLinkCreatedEvent(LinkService::TYPE_EMAIL));
    }

    public function testSkipsProcessingIfDisabled(): void
    {
        $this->tinyUrlApiMock->expects(self::never())->method('getTinyUrl');

        $this->typoLinkHook->__invoke($this->getAfterLinkCreatedEvent(tinyUrlEnabled: false));
    }

    public function testUrlIsReplacedWithTinyUrl(): void
    {
        $this->tinyUrlApiMock->method('getTinyUrl')->willReturn('http://the-tiny-url');

        $event = $this->getAfterLinkCreatedEvent();
        $this->typoLinkHook->__invoke($event);

        self::assertSame('http://the-tiny-url', $event->getLinkResult()->getUrl());
    }

    protected function getAfterLinkCreatedEvent(
        string $typoLinkType = LinkService::TYPE_URL,
        $tinyUrlEnabled = true,
        $tinyUrlConfig = [],
    ): AfterLinkIsGeneratedEvent {
        $this->linkInstructions = [
            'tinyurl' => $tinyUrlEnabled,
            'someother' => 'config',
        ];

        if ($tinyUrlConfig !== []) {
            $this->linkInstructions['tinyurl.'] = $tinyUrlConfig;
        }

        $link = new LinkResult($typoLinkType, self::TARGET_URL);

        return new AfterLinkIsGeneratedEvent($link, $this->contentObjectRendererMock, $this->linkInstructions);
    }
}
