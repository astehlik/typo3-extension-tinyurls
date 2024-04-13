<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Configuration;

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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use DateTimeImmutable;

class TypoScriptConfiguratorTest extends TestCase
{
    private ContentObjectRenderer|MockObject $contentObjectRendererMock;

    private MockObject|TinyUrl $tinyUrlMock;

    private TypoScriptConfigurator $typoScriptConfigurator;

    protected function setUp(): void
    {
        $this->tinyUrlMock = $this->createMock(TinyUrl::class);
        $this->contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);
        $this->typoScriptConfigurator = new TypoScriptConfigurator();
    }

    public function testDoesNotSetOptionsIfConfigIsEmpty(): void
    {
        $this->tinyUrlMock->expects(self::never())->method('disableDeleteOnUse');
        $this->tinyUrlMock->expects(self::never())->method('enableDeleteOnUse');
        $this->tinyUrlMock->expects(self::never())->method('setCustomUrlKey');
        $this->tinyUrlMock->expects(self::never())->method('resetCustomUrlKey');
        $this->tinyUrlMock->expects(self::never())->method('setValidUntil');
        $this->tinyUrlMock->expects(self::never())->method('resetValidUntil');

        $this->initializeConfigFromTyposcript();
    }

    public function testOptionValueIsProcessedByStdwrapIfConfigured(): void
    {
        $this->contentObjectRendererMock->expects(self::once())
            ->method('stdWrap')
            ->with('asdf', ['case' => 'upper'])
            ->willReturn('ASDF');

        $this->tinyUrlMock->expects(self::once())
            ->method('setCustomUrlKey')
            ->with('ASDF');

        $this->initializeConfigFromTyposcript(
            [
                'tinyurl.' => [
                    'urlKey' => 'asdf',
                    'urlKey.' => ['case' => 'upper'],
                ],
            ],
        );
    }

    public function testSetsOptionDeleteOnUseDefaultVlaue(): void
    {
        $this->tinyUrlMock->expects(self::once())
            ->method('disableDeleteOnUse');
        $this->tinyUrlMock->expects(self::never())
            ->method('enableDeleteOnUse');

        $this->initializeConfigFromTyposcript(
            ['tinyurl.' => []],
        );
    }

    public function testSetsOptionDeleteOnUseValueFromConfig(): void
    {
        $this->tinyUrlMock->expects(self::once())
            ->method('enableDeleteOnUse');
        $this->tinyUrlMock->expects(self::never())
            ->method('disableDeleteOnUse');

        $this->initializeConfigFromTyposcript(
            ['tinyurl.' => ['deleteOnUse' => '1']],
        );
    }

    public function testSetsOptionUrlKeyDefaultValue(): void
    {
        $this->tinyUrlMock->expects(self::once())
            ->method('resetCustomUrlKey');

        $this->initializeConfigFromTyposcript(
            ['tinyurl.' => []],
        );
    }

    public function testSetsOptionUrlKeyWithValueFromConfig(): void
    {
        $this->tinyUrlMock->expects(self::once())
            ->method('setCustomUrlKey')
            ->with('the-new-url-key');

        $this->initializeConfigFromTyposcript(
            ['tinyurl.' => ['urlKey' => 'the-new-url-key']],
        );
    }

    public function testSetsOptionValidUntilDefaultValue(): void
    {
        $this->tinyUrlMock->expects(self::once())
            ->method('resetValidUntil');
        $this->tinyUrlMock->expects(self::never())
            ->method('setValidUntil');

        $this->initializeConfigFromTyposcript(
            ['tinyurl.' => []],
        );
    }

    public function testSetsOptionValidUntilWithValueFromConfig(): void
    {
        $this->tinyUrlMock->expects(self::never())
            ->method('resetValidUntil');
        $this->tinyUrlMock->expects(self::once())
            ->method('setValidUntil')
            ->with(self::callback(static fn(DateTimeImmutable $dateTime) => $dateTime->getTimestamp() === 2389));

        $this->initializeConfigFromTyposcript(
            ['tinyurl.' => ['validUntil' => 2389]],
        );
    }

    private function initializeConfigFromTyposcript(array $config = []): void
    {
        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            $this->tinyUrlMock,
            $config,
            $this->contentObjectRendererMock,
        );
    }
}
