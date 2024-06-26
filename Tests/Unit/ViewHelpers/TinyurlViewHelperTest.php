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
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface;
use Tx\Tinyurls\ViewHelpers\TinyurlViewHelper;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TinyurlViewHelperTest extends UnitTestCase
{
    private MockObject|TinyUrlGeneratorInterface $tinyUrlGeneratorMock;

    private TinyurlViewHelper $tinyUrlViewHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tinyUrlGeneratorMock = $this->createMock(TinyUrlGeneratorInterface::class);

        $this->tinyUrlViewHelper = new TinyurlViewHelper($this->tinyUrlGeneratorMock);
    }

    public function testCustomUrlKeyIsPassedToTinyUrlApi(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrlForSite')
            ->with(self::callback(static fn(TinyUrl $tinyUrl): bool => $tinyUrl->getCustomUrlKey() === 'theurl-key'));

        $arguments = [
            'url' => 'http://the-url.tld',
            'urlKey' => 'theurl-key',
        ];

        $this->callRender($arguments);
    }

    public function testOnlyOneTimeValidSetsDeleteOnUse(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrlForSite')
            ->with(self::callback(static fn(TinyUrl $tinyUrl): bool => $tinyUrl->getDeleteOnUse() === true));

        $arguments = [
            'url' => 'http://www.url.tld',
            'onlyOneTimeValid' => true,
        ];

        $this->callRender($arguments);
    }

    public function testRetrievesUrlFromRenderChildrenIfNotProvidedAsArgument(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrlForSite')
            ->with(
                self::callback(
                    static fn(TinyUrl $tinyUrl): bool => $tinyUrl->getTargetUrl() === 'http://the-children-url.tld',
                ),
            );

        $this->callRender(childrenOutput: 'http://the-children-url.tld');
    }

    public function testSiteIsPassedToUrlGenerator(): void
    {
        $site = $this->createMock(SiteInterface::class);

        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrlForSite')
            ->with(self::isInstanceOf(TinyUrl::class), $site);

        $arguments = [
            'url' => 'http://the-url.tld',
            'site' => $site,
        ];

        $this->callRender($arguments);
    }

    public function testUrlGenerationIsSkippedIfUrlIsEmpty(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::never())
            ->method('generateTinyUrlForSite');

        $this->callRender();
    }

    public function testUrlIsPassedToTinyUrlApi(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrlForSite')
            ->with(
                self::callback(static fn(TinyUrl $tinyUrl): bool => $tinyUrl->getTargetUrl() === 'http://the-url.tld'),
            );

        $arguments = ['url' => 'http://the-url.tld'];
        $this->callRender($arguments);
    }

    public function testValidUntilIsPassedToTinyUrlApi(): void
    {
        $this->tinyUrlGeneratorMock->expects(self::once())
            ->method('generateTinyUrlForSite')
            ->with(
                self::callback(
                    static fn(TinyUrl $tinyUrl): bool => $tinyUrl->getValidUntil()->getTimestamp() === 3848909,
                ),
            );

        $arguments = [
            'url' => 'http://the-url.tld',
            'validUntil' => 3848909,
        ];

        $this->callRender($arguments);
    }

    private function callRender(array $arguments = [], string $childrenOutput = ''): void
    {
        $defaultArguments = [
            'url' => null,
            'onlyOneTimeValid' => false,
            'validUntil' => 0,
            'urlKey' => '',
            'site' => null,
        ];

        $arguments = array_merge($defaultArguments, $arguments);

        $this->tinyUrlViewHelper->setArguments($arguments);

        $this->tinyUrlViewHelper->setRenderChildrenClosure(static fn() => $childrenOutput);

        $this->tinyUrlViewHelper->render();
    }
}
