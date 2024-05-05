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
use Tx\Tinyurls\Configuration\TypoScriptConfigurator;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Hooks\TypoLink;
use Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;
use TYPO3\CMS\Frontend\Typolink\LinkResult;

class TypoLinkTest extends TestCase
{
    private const TARGET_URL = 'https://the-tiny-url.tld';

    private ContentObjectRenderer|MockObject $contentObjectRendererMock;

    private array $linkInstructions;

    private MockObject|TinyUrlGeneratorInterface $tinyUrlGeneratorMock;

    private TypoLink $typoLinkHook;

    private MockObject|TypoScriptConfigurator $typoScriptConfigurator;

    protected function setUp(): void
    {
        $this->tinyUrlGeneratorMock = $this->createMock(TinyUrlGeneratorInterface::class);
        $this->typoScriptConfigurator = $this->createMock(TypoScriptConfigurator::class);
        $this->contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);

        $this->typoLinkHook = new TypoLink(
            $this->tinyUrlGeneratorMock,
            $this->typoScriptConfigurator,
        );
    }

    public function testApiGetTinyUrlIsCalledWithOriginalUrl(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrlForSite')
            ->with(
                self::callback(static fn(TinyUrl $tinyUrl) => $tinyUrl->getTargetUrl() === self::TARGET_URL),
                null,
            )
            ->willReturn('the tiny url');

        $this->typoLinkHook->__invoke($this->getAfterLinkCreatedEvent());
    }

    public function testApiIsInitializedWithLinkConfig(): void
    {
        $this->tinyUrlGeneratorMock->method('generateTinyUrlForSite')->willReturn('the url');

        $event = $this->getAfterLinkCreatedEvent(tinyUrlConfig: ['the' => 'config']);

        $this->typoScriptConfigurator->expects(self::once())
            ->method('initializeConfigFromTyposcript')
            ->with(
                self::isInstanceOf(TinyUrl::class),
                $this->linkInstructions,
                $this->contentObjectRendererMock,
            );

        $this->typoLinkHook->__invoke($event);
    }

    public function testSkipsProcessingForMailtoUrl(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::never())->method('generateTinyUrlForSite');

        $this->typoLinkHook->__invoke($this->getAfterLinkCreatedEvent(LinkService::TYPE_EMAIL));
    }

    public function testSkipsProcessingIfDisabled(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::never())->method('generateTinyUrlForSite');

        $this->typoLinkHook->__invoke($this->getAfterLinkCreatedEvent(tinyUrlEnabled: false));
    }

    public function testUrlIsReplacedWithTinyUrl(): void
    {
        $this->tinyUrlGeneratorMock->method('generateTinyUrlForSite')->willReturn('http://the-tiny-url');

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
