<?php
declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Controller;

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
use Psr\Http\Message\ResponseInterface;
use Tx\Tinyurls\Controller\EidController;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\NoTinyUrlKeySubmittedException;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * @backupGlobals enabled
 */
class EidControllerTest extends TestCase
{
    /**
     * @var EidController
     */
    protected $eidController;

    /**
     * @var TinyUrlRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tinyUrlRepositoryMock;

    /**
     * @var TypoScriptFrontendController|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tsfeMock;

    protected function setUp(): void
    {
        $this->tsfeMock = $this->createMock(TypoScriptFrontendController::class);
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);

        $this->eidController = new EidController();
        $this->eidController->setTypoScriptFrontendController($this->tsfeMock);
        $this->eidController->injectTinyUrlRepository($this->tinyUrlRepositoryMock);

        $GLOBALS['EXEC_TIME'] = time();
    }

    public function testBadRequestExceptionIfNoUrlKeyIsProvided()
    {
        $this->expectException(BadRequestException::class);

        $this->processRequest();
    }

    public function testDeleteOnUseUrlIsDeleted()
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(true);
        $tinyUrlMock->method('getUrlkey')->willReturn('thekey');

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('deleteTinyUrlByKey')
            ->with('thekey');

        $this->processRequest();
    }

    public function testHitIsCountedIfUrlIsNotDeletedOnUse()
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(999);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(false);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('countTinyUrlHit')
            ->with($tinyUrlMock);

        $this->processRequest();
    }

    public function testHitIsNotCountedIfUrlIsDeletedOnUse()
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(true);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects($this->never())
            ->method('countTinyUrlHit');

        $this->processRequest();
    }

    public function testInvalidUrlsArePurgedBeforeRedirect()
    {
        $this->expectException(NoTinyUrlKeySubmittedException::class);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('purgeInvalidUrls');

        $this->processRequest();
    }

    public function testPageNotFoundErrorIfUrlKeyIsNotFoundInDatabase()
    {
        $this->expectException(PageNotFoundException::class);

        $_GET['tx_tinyurls']['key'] = 'thekey';

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->with('thekey')
            ->willThrowException(new TinyUrlNotFoundException('thekey'));

        $this->tsfeMock->expects($this->once())
            ->method('pageNotFoundAndExit')
            ->with('The tinyurl with the key thekey was not found.');

        $this->processRequest();
    }

    public function testRedirectsToTargetUrl()
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(666);
        $tinyUrlMock->method('getTargetUrl')->willReturn('http://the-target.url');
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(false);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $response = $this->processRequest();
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('Moved Permanently', $response->getReasonPhrase());
        $this->assertEquals('http://the-target.url', $response->getHeaderLine('Location'));
    }

    /**
     * @param string $headerName
     * @param string $expectedValue
     * @dataProvider tinyUrlRedirectSendsNoCacheHeadersDataProvider
     * @test
     */
    public function tinyUrlRedirectSendsNoCacheHeaders(string $headerName, string $expectedValue)
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(true);

        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $response = $this->processRequest();

        if ($expectedValue === 'gmdate') {
            $expectedValue = gmdate('D, d M Y H:i:s', $GLOBALS['EXEC_TIME']) . ' GMT';
        }

        $this->assertEquals($expectedValue, $response->getHeaderLine($headerName));
    }

    public function tinyUrlRedirectSendsNoCacheHeadersDataProvider(): array
    {
        return [
            [
                'Expires',
                '0',
            ],
            [
                'Last-Modified',
                'gmdate',
            ],
            [
                'Cache-Control',
                'no-cache, must-revalidate',
            ],
            [
                'Pragma',
                'no-cache',
            ],
        ];
    }

    private function processRequest(): ResponseInterface
    {
        $request = new ServerRequest();
        $request = $request->withQueryParams($_GET);
        $response = $this->eidController->tinyUrlRedirect($request, new Response());
        return $response;
    }
}
