<?php
declare(strict_types = 1);
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

use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use Tx\Tinyurls\Utils\UrlUtils;

class TinyUrlGeneratorTest extends TestCase
{
    /**
     * @var TinyUrlGenerator
     */
    protected $tinyUrlGenerator;

    /**
     * @var TinyUrlRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tinyUrlRepositoryMock;

    /**
     * @var UrlUtils|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlUtilsMock;

    protected function setUp()
    {
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);
        $this->urlUtilsMock = $this->createMock(UrlUtils::class);
        $this->tinyUrlGenerator = new TinyUrlGenerator();
        $this->tinyUrlGenerator->injectTinyUrlRepository($this->tinyUrlRepositoryMock);
        $this->tinyUrlGenerator->injectUrlUtils($this->urlUtilsMock);
    }

    public function testBuildTinyUrlUsesUrlUtilToBuildUrl()
    {
        $urlKey = 'thekey';
        $this->urlUtilsMock->expects($this->once())
            ->method('buildTinyUrl')
            ->with($urlKey)
            ->willReturn('http://the-url.tld/goto/' . $urlKey);
        $tinyUrl = $this->tinyUrlGenerator->buildTinyUrl($urlKey);
        $this->assertEquals('http://the-url.tld/goto/' . $urlKey, $tinyUrl);
    }

    public function testGetTinyUrlBuildsUrlForExistingUrl()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('theKey');
        $tinyUrl->persistPreProcess();

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willReturn($tinyUrl);

        $this->urlUtilsMock->expects($this->once())
            ->method('buildTinyUrl')
            ->with('theKey')
            ->willReturn('http://the-tiny.url');

        $generatedUrl = $this->tinyUrlGenerator->getTinyUrl('http://the-target.url');
        $this->assertEquals('http://the-tiny.url', $generatedUrl);
    }

    public function testGetTinyUrlBuildsUrlForNonExistingUrl()
    {
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('insertNewTinyUrl')
            ->with(
                $this->callback(
                    function (TinyUrl $theNewTinyUrl) {
                        $theNewTinyUrl->setCustomUrlKey('theKey');
                        $theNewTinyUrl->persistPreProcess();
                        $theNewTinyUrl->persistPostProcessInsert(234);
                        return true;
                    }
                )
            );

        $this->urlUtilsMock->expects($this->once())
            ->method('buildTinyUrl')
            ->with('theKey')
            ->willReturn('http://the-tiny.url');

        $generatedUrl = $this->tinyUrlGenerator->getTinyUrl('http://the-target.url');
        $this->assertEquals('http://the-tiny.url', $generatedUrl);
    }

    public function testGetTinyUrlGeneratesNewTinyUrlForNonExistingUrl()
    {
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('insertNewTinyUrl');

        $this->tinyUrlGenerator->getTinyUrl('http://the-target.url');
    }

    public function testGetTinyUrlReturnsEmptyStringForEmptyTargetUrl()
    {
        $this->assertEquals('', $this->tinyUrlGenerator->getTinyUrl(''));
    }

    public function testSetCommentSetsCommentForNewTinyUrl()
    {
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('insertNewTinyUrl')
            ->with(
                $this->callback(
                    function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getComment() === 'the comment';
                    }
                )
            );

        $this->tinyUrlGenerator->setComment('the comment');
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionDeleteOnUseSetsDeleteOnUseForNewTinyUrl()
    {
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('insertNewTinyUrl')
            ->with(
                $this->callback(
                    function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getDeleteOnUse() === true;
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionDeleteOnUse(true);
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionUrlKeyDoesNotSetCustomUrlKeyForNewTinyUrlIfEmpty()
    {
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('insertNewTinyUrl')
            ->with(
                $this->callback(
                    function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->hasCustomUrlKey() === false;
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionUrlKey('');
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionUrlKeySetsCustomUrlKeyForNewTinyUrl()
    {
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('insertNewTinyUrl')
            ->with(
                $this->callback(
                    function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getCustomUrlKey() === 'the custom key';
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionUrlKey('the custom key');
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }

    public function testSetOptionValidUntilSetsValidUntilForNewTinyUrl()
    {
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByTargetUrl')
            ->willThrowException(new TinyUrlNotFoundException());

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('insertNewTinyUrl')
            ->with(
                $this->callback(
                    function (TinyUrl $theNewTinyUrl) {
                        return $theNewTinyUrl->getValidUntil()->diff(new \DateTime('2027-12-16 03:51:30'))->s === 0;
                    }
                )
            );

        $this->tinyUrlGenerator->setOptionValidUntil(1828929090);
        $this->tinyUrlGenerator->getTinyUrl('http://the-url.tld');
    }
}
