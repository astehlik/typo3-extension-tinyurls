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
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use Tx\Tinyurls\Utils\UrlUtils;
use DateTime;

class TinyUrlGeneratorTest extends TestCase
{
    private TinyUrlGenerator $tinyUrlGenerator;

    private MockObject|TinyUrlRepository $tinyUrlRepositoryMock;

    private MockObject|UrlUtils $urlUtilsMock;

    protected function setUp(): void
    {
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);
        $this->urlUtilsMock = $this->createMock(UrlUtils::class);

        $this->tinyUrlGenerator = new TinyUrlGenerator(
            $this->tinyUrlRepositoryMock,
            new TypoScriptConfigurator(),
            $this->urlUtilsMock
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

        $generatedUrl = $this->tinyUrlGenerator->getTinyUrl('http://the-target.url');
        self::assertSame('http://the-tiny.url', $generatedUrl);
    }

    public function testGetTinyUrlBuildsUrlForNonExistingUrl(): void
    {
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl')
            ->with(
                self::callback(
                    static function (TinyUrl $theNewTinyUrl) {
                        $theNewTinyUrl->setCustomUrlKey('theKey');
                        $theNewTinyUrl->persistPreProcess();
                        $theNewTinyUrl->persistPostProcessInsert(234);
                        return true;
                    }
                )
            );

        $this->urlUtilsMock->expects(self::once())
            ->method('buildTinyUrl')
            ->with('theKey')
            ->willReturn('http://the-tiny.url');

        $generatedUrl = $this->tinyUrlGenerator->getTinyUrl('http://the-target.url');
        self::assertSame('http://the-tiny.url', $generatedUrl);
    }

    public function testGetTinyUrlGeneratesNewTinyUrlForNonExistingUrl(): void
    {
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl');

        $this->tinyUrlGenerator->getTinyUrl('http://the-target.url');
    }

    public function testGetTinyUrlReturnsEmptyStringForEmptyTargetUrl(): void
    {
        self::assertSame('', $this->tinyUrlGenerator->getTinyUrl(''));
    }

    public function testSetCommentSetsCommentForNewTinyUrl(): void
    {
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl')
            ->with(
                self::callback(
                    static function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getComment() === 'the comment';
                    }
                )
            );

        $this->tinyUrlGenerator->setComment('the comment');
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionDeleteOnUseSetsDeleteOnUseForNewTinyUrl(): void
    {
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl')
            ->with(
                self::callback(
                    static function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getDeleteOnUse() === true;
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionDeleteOnUse(true);
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionUrlKeyDoesNotSetCustomUrlKeyForNewTinyUrlIfEmpty(): void
    {
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl')
            ->with(
                self::callback(
                    static function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->hasCustomUrlKey() === false;
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionUrlKey('');
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionUrlKeySetsCustomUrlKeyForNewTinyUrl(): void
    {
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl')
            ->with(
                self::callback(
                    static function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getCustomUrlKey() === 'the custom key';
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionUrlKey('the custom key');
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionValidUntilSetsValidUntilForNewTinyUrl(): void
    {
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('insertNewTinyUrl')
            ->with(
                self::callback(
                    static function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getValidUntil()->diff(new DateTime('2027-12-16 03:51:30'))->s === 0;
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionValidUntil(1828929090);
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }
}
