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
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TypoScriptConfiguratorTest extends TestCase
{
    /**
     * @var ContentObjectRenderer|MockObject
     */
    protected $contentObjectRendererMock;

    /**
     * @var TinyUrlGenerator|MockObject
     */
    protected $tinyUrlGeneratorMock;

    /**
     * @var TypoScriptConfigurator
     */
    protected $typoScriptConfigurator;

    protected function setUp(): void
    {
        $this->tinyUrlGeneratorMock = $this->createMock(TinyUrlGenerator::class);
        $this->contentObjectRendererMock = $this->createMock(ContentObjectRenderer::class);
        $this->typoScriptConfigurator = new TypoScriptConfigurator($this->tinyUrlGeneratorMock);
    }

    public function testDoesNotSetOptionsIfConfigIsEmpty()
    {
        $this->typoScriptConfigurator->initializeConfigFromTyposcript([], $this->contentObjectRendererMock);
        $this->tinyUrlGeneratorMock->expects($this->never())->method('setOptionDeleteOnUse');
        $this->tinyUrlGeneratorMock->expects($this->never())->method('setOptionUrlKey');
        $this->tinyUrlGeneratorMock->expects($this->never())->method('setOptionValidUntil');
    }

    public function testOptionValueIsProcessedByStdwrapIfConfigured()
    {
        $this->contentObjectRendererMock->expects($this->once())
            ->method('stdWrap')
            ->with('asdf', ['case' => 'upper'])
            ->willReturn('ASDF');

        $this->tinyUrlGeneratorMock->expects($this->once())
            ->method('setOptionUrlKey')
            ->with('ASDF');

        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            [
                'tinyurl.' => [
                    'urlKey' => 'asdf',
                    'urlKey.' => ['case' => 'upper'],
                ],
            ],
            $this->contentObjectRendererMock
        );
    }

    public function testSetsOptionDeleteOnUseDefaultVlaue()
    {
        $this->tinyUrlGeneratorMock->expects($this->once())
            ->method('setOptionDeleteOnUse')
            ->with(false);

        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            ['tinyurl.' => []],
            $this->contentObjectRendererMock
        );
    }

    public function testSetsOptionDeleteOnUseValueFromConfig()
    {
        $this->tinyUrlGeneratorMock->expects($this->once())
            ->method('setOptionDeleteOnUse')
            ->with(true);

        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            ['tinyurl.' => ['deleteOnUse' => '1']],
            $this->contentObjectRendererMock
        );
    }

    public function testSetsOptionUrlKeyDefaultValue()
    {
        $this->tinyUrlGeneratorMock->expects($this->once())
            ->method('setOptionUrlKey')
            ->with(false);

        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            ['tinyurl.' => []],
            $this->contentObjectRendererMock
        );
    }

    public function testSetsOptionUrlKeyWithValueFromConfig()
    {
        $this->tinyUrlGeneratorMock->expects($this->once())
            ->method('setOptionUrlKey')
            ->with('the-new-url-key');

        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            ['tinyurl.' => ['urlKey' => 'the-new-url-key']],
            $this->contentObjectRendererMock
        );
    }

    public function testSetsOptionValidUntilDefaultValue()
    {
        $this->tinyUrlGeneratorMock->expects($this->once())
            ->method('setOptionValidUntil')
            ->with(0);

        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            ['tinyurl.' => []],
            $this->contentObjectRendererMock
        );
    }

    public function testSetsOptionValidUntilWithValueFromConfig()
    {
        $this->tinyUrlGeneratorMock->expects($this->once())
            ->method('setOptionValidUntil')
            ->with(2389);

        $this->typoScriptConfigurator->initializeConfigFromTyposcript(
            ['tinyurl.' => ['validUntil' => 2389]],
            $this->contentObjectRendererMock
        );
    }
}
