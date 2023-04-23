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
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ApiTest extends TestCase
{
    /**
     * @var Api
     */
    protected $tinyUrlApi;

    /**
     * @var MockObject|TinyUrlGenerator
     */
    protected $tinyUrlGeneratorMock;

    protected function setUp(): void
    {
        $this->tinyUrlGeneratorMock = $this->createMock(TinyUrlGenerator::class);

        $this->tinyUrlApi = new Api();
        $this->tinyUrlApi->setTinyUrlGenerator($this->tinyUrlGeneratorMock);
    }

    public function testGetTinyUrlUsesTinyUrlGeneratorForCreatingUrl(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('getTinyUrl')
            ->with('http://the-url.tld')
            ->willReturn('http://the-tiny.url');

        self::assertSame('http://the-tiny.url', $this->tinyUrlApi->getTinyUrl('http://the-url.tld'));
    }

    public function testInitializeConfigFromTypoScriptUsesTypoScriptConfiguratorForSettingConfig(): void
    {
        $config = ['the' => 'config'];

        /** @var ContentObjectRenderer $contentObjectRendererMock */
        $contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);

        /** @var MockObject|TypoScriptConfigurator $typoScriptConfiguratorMock */
        $typoScriptConfiguratorMock = $this->createMock(TypoScriptConfigurator::class);
        $this->tinyUrlApi->setTypoScriptConfigurator($typoScriptConfiguratorMock);

        $typoScriptConfiguratorMock->expects(self::once())
            ->method('initializeConfigFromTyposcript')
            ->with($config, $contentObjectRendererMock);
        $this->tinyUrlApi->initializeConfigFromTyposcript($config, $contentObjectRendererMock);
    }

    public function testSetCommentSetsCommentInUrlGenerator(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('setComment')
            ->with('the comment');

        $this->tinyUrlApi->setComment('the comment');
    }

    public function testSetDeleteOnUseSetsDeleteOnUseOptionInUrlGenerator(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('setOptionDeleteOnUse')
            ->with(true);

        $this->tinyUrlApi->setDeleteOnUse(true);
    }

    public function testSetUrlKeySetsUrlKeyOptionInUrlGenerator(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('setOptionUrlKey')
            ->with('the url key');

        $this->tinyUrlApi->setUrlKey('the url key');
    }

    public function testSetValidUntilSetsValidUntilOptionInUrlGenerator(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('setOptionValidUntil')
            ->with(12434);

        $this->tinyUrlApi->setValidUntil(12434);
    }
}
