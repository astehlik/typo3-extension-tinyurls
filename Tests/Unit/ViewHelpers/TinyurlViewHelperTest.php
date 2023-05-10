<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\ViewHelpers;

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
use Tx\Tinyurls\TinyUrl\Api;
use Tx\Tinyurls\ViewHelpers\TinyurlViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class TinyurlViewHelperTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @var Api|MockObject
     */
    protected $tinyUrlApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tinyUrlApi = $this->createMock(Api::class);
        TinyurlViewHelper::setTinyUrlApi($this->tinyUrlApi);
    }

    public function testCustomUrlKeyIsPassedToTinyUrlApi(): void
    {
        $this->tinyUrlApi->expects(self::once())
            ->method('setUrlKey')
            ->with('theurl-key');

        $arguments = [
            'url' => 'http://the-url.tld',
            'urlKey' => 'theurl-key',
        ];

        $this->callRenderStatic($arguments);
    }

    public function testOnlyOneTimeValidSetsDeleteOnUse(): void
    {
        $this->tinyUrlApi->expects(self::once())
            ->method('setDeleteOnUse')
            ->with(true);

        $arguments = [
            'url' => 'http://www.url.tld',
            'onlyOneTimeValid' => true,
        ];
        $this->callRenderStatic($arguments);
    }

    public function testRetrievesUrlFromRenderChildrenIfNotProvidedAsArgument(): void
    {
        $renderChildrenClosure = function () {
            return 'http://the-children-url.tld';
        };

        $this->tinyUrlApi->expects(self::once())
            ->method('getTinyUrl')
            ->with('http://the-children-url.tld')
            ->willReturn('');

        $this->callRenderStaticWithRenderChildrenClosure([], $renderChildrenClosure);
    }

    public function testUrlIsPassedToTinyUrlApi(): void
    {
        $this->tinyUrlApi->expects(self::once())
            ->method('getTinyUrl')
            ->with('http://the-url.tld')
            ->willReturn('');

        $arguments = ['url' => 'http://the-url.tld'];
        $this->callRenderStatic($arguments);
    }

    public function testValidUntilIsPassedToTinyUrlApi(): void
    {
        $this->tinyUrlApi->expects(self::once())
            ->method('setValidUntil')
            ->with(3848909);

        $arguments = [
            'url' => 'http://the-url.tld',
            'validUntil' => 3848909,
        ];

        $this->callRenderStatic($arguments);
    }

    private function buildArguments(array $arguments)
    {
        $viewHelper = new TinyurlViewHelper();
        $argumentDefinitions = $viewHelper->prepareArguments();
        foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
            if (!isset($arguments[$argumentName])) {
                $arguments[$argumentName] = $argumentDefinition->getDefaultValue();
            }
        }
        return $arguments;
    }

    private function callRenderStatic(array $arguments): void
    {
        $renderChildrenClosure = function (): void {
            // Nothing to do here.
        };

        $this->callRenderStaticWithRenderChildrenClosure($arguments, $renderChildrenClosure);
    }

    private function callRenderStaticWithRenderChildrenClosure(array $arguments, \Closure $renderChildrenClosure): void
    {
        TinyurlViewHelper::renderStatic(
            $this->buildArguments($arguments),
            $renderChildrenClosure,
            $this->createMock(RenderingContextInterface::class)
        );
    }
}
