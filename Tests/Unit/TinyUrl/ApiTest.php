<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\TinyUrl;

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
use Tx\Tinyurls\TinyUrl\Api;
use Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ApiTest extends TestCase
{
    private Api $tinyUrlApi;

    private MockObject|TinyUrlGeneratorInterface $tinyUrlGeneratorMock;

    private MockObject|TypoScriptConfigurator $typoScriptConfiguratorMock;

    protected function setUp(): void
    {
        $this->tinyUrlGeneratorMock = $this->createMock(TinyUrlGeneratorInterface::class);
        $this->typoScriptConfiguratorMock = $this->createMock(TypoScriptConfigurator::class);

        $this->tinyUrlApi = new Api($this->tinyUrlGeneratorMock, $this->typoScriptConfiguratorMock);
    }

    public function testGetTinyUrlUsesTinyUrlGeneratorForCreatingUrl(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrl')
            ->willReturn('http://the-tiny.url');

        self::assertSame('http://the-tiny.url', $this->tinyUrlApi->getTinyUrl('http://the-url.tld'));
    }

    public function testInitializeConfigFromTypoScriptUsesTypoScriptConfiguratorForSettingConfig(): void
    {
        $config = ['the' => 'config'];

        /** @var ContentObjectRenderer $contentObjectRendererMock */
        $contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);

        $this->typoScriptConfiguratorMock->expects(self::once())
            ->method('initializeConfigFromTyposcript')
            ->with($this->tinyUrlApi->getTinyUrlInstance(), $config, $contentObjectRendererMock);

        $this->tinyUrlApi->initializeConfigFromTyposcript($config, $contentObjectRendererMock);
    }

    public function testSetCommentSetsCommentInUrlGenerator(): void
    {
        $this->tinyUrlApi->setComment('the comment');

        self::assertSame('the comment', $this->tinyUrlApi->getTinyUrlInstance()->getComment());
    }

    public function testSetDeleteOnUseSetsDeleteOnUseOptionInUrlGenerator(): void
    {
        $this->tinyUrlApi->setDeleteOnUse(true);

        self::assertTrue($this->tinyUrlApi->getTinyUrlInstance()->getDeleteOnUse());
    }

    public function testSetUrlKeySetsUrlKeyOptionInUrlGenerator(): void
    {
        $this->tinyUrlApi->setUrlKey('the url key');

        self::assertSame('the url key', $this->tinyUrlApi->getTinyUrlInstance()->getCustomUrlKey());
    }

    public function testSetValidUntilSetsValidUntilOptionInUrlGenerator(): void
    {
        $this->tinyUrlApi->setValidUntil(12434);

        self::assertSame(12434, $this->tinyUrlApi->getTinyUrlInstance()->getValidUntil()->getTimestamp());
    }
}
