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
use RuntimeException;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use Tx\Tinyurls\Utils\UrlUtilsInterface;

class TinyUrlGeneratorTest extends TestCase
{
    private ExtensionConfiguration|MockObject $extensionConfigurationMock;

    private TinyUrlGenerator $tinyUrlGenerator;

    private MockObject|TinyUrlRepository $tinyUrlRepositoryMock;

    private MockObject|UrlUtilsInterface $urlUtilsMock;

    protected function setUp(): void
    {
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);
        $this->urlUtilsMock = $this->createMock(UrlUtilsInterface::class);

        $this->tinyUrlGenerator = new TinyUrlGenerator(
            $this->extensionConfigurationMock,
            $this->tinyUrlRepositoryMock,
            $this->urlUtilsMock,
        );
    }

    public function testGetTinyUrlBuildsUrlForExistingUrl(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('theKey');
        $tinyUrl->persistPreProcess();

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willReturn($tinyUrl);

        $this->urlUtilsMock->expects(self::once())
            ->method('buildTinyUrl')
            ->with('theKey')
            ->willReturn('http://the-tiny.url');

        $generatedUrl = $this->tinyUrlGenerator->generateTinyUrl(TinyUrl::createForUrl('http://the-target.url'));
        self::assertSame('http://the-tiny.url', $generatedUrl);
    }

    public function testGetTinyUrlBuildsUrlForNonExistingUrl(): void
    {
        $tinyUrl = TinyUrl::createForUrl('http://the-target.url');
        $tinyUrl->setGeneratedUrlKey('theKey');

        $this->tinyUrlRepositoryMock->expects(self::exactly(2))
            ->method('findTinyUrlByTargetUrl')
            ->willReturnCallback(
                static function () use ($tinyUrl) {
                    static $callCount = 0;
                    $callCount++;
                    return match ($callCount) {
                        1 => throw new TinyUrlNotFoundException(),
                        2 => $tinyUrl,
                        default => throw new RuntimeException('Unexpected call count: ' . $callCount),
                    };
                },
            );

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl')
            ->with($tinyUrl);

        $this->urlUtilsMock->expects(self::once())
            ->method('buildTinyUrl')
            ->with('theKey')
            ->willReturn('http://the-tiny.url');

        $generatedUrl = $this->tinyUrlGenerator->generateTinyUrl($tinyUrl);
        self::assertSame('http://the-tiny.url', $generatedUrl);
    }

    public function testGetTinyUrlReturnsEmptyStringForEmptyTargetUrl(): void
    {
        self::assertSame('', $this->tinyUrlGenerator->generateTinyUrl(TinyUrl::createNew()));
    }
}
