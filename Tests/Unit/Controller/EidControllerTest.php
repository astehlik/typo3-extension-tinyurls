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

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Controller\EidController;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\NoTinyUrlKeySubmittedException;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Frontend\Controller\ErrorController;

#[BackupGlobals(true)]
class EidControllerTest extends TestCase
{
    private EidController $eidController;

    private ErrorController|MockObject $errorControllerMock;

    private ExtensionConfiguration|MockObject $extensionConfigurationMock;

    private MockObject|SiteMatcher $siteMatcherMock;

    private MockObject|TinyUrlRepository $tinyUrlRepositoryMock;

    protected function setUp(): void
    {
        $this->errorControllerMock = $this->createMock(ErrorController::class);
        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $this->siteMatcherMock = $this->createMock(SiteMatcher::class);
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);

        $this->eidController = new EidController(
            $this->extensionConfigurationMock,
            $this->siteMatcherMock,
            $this->tinyUrlRepositoryMock,
        );
        $this->eidController->setErrorController($this->errorControllerMock);

        $GLOBALS['EXEC_TIME'] = time();
    }

    public static function provideTinyUrlRedirectSendsNoCacheHeadersCases(): iterable
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

    public function testBadRequestExceptionIfNoUrlKeyIsProvided(): void
    {
        $this->expectException(BadRequestException::class);

        $this->processRequest();
    }

    public function testDeleteOnUseUrlIsDeleted(): void
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(true);
        $tinyUrlMock->method('getUrlkey')->willReturn('thekey');

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('deleteTinyUrlByKey')
            ->with('thekey');

        $this->processRequest();
    }

    public function testHitIsCountedIfUrlIsNotDeletedOnUse(): void
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(999);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(false);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('countTinyUrlHit')
            ->with($tinyUrlMock);

        $this->processRequest();
    }

    public function testHitIsNotCountedIfUrlIsDeletedOnUse(): void
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(true);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $this->tinyUrlRepositoryMock->expects(self::never())
            ->method('countTinyUrlHit');

        $this->processRequest();
    }

    public function testInvalidUrlsArePurgedBeforeRedirect(): void
    {
        $this->expectException(NoTinyUrlKeySubmittedException::class);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('purgeInvalidUrls');

        $this->processRequest();
    }

    public function testPageNotFoundErrorIfUrlKeyIsNotFoundInDatabase(): void
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->with('thekey')
            ->willThrowException(new TinyUrlNotFoundException('thekey'));

        $errorResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->errorControllerMock->expects(self::once())
            ->method('pageNotFoundAction')
            ->with(
                self::isInstanceOf(ServerRequestInterface::class),
                'The tinyurl with the key thekey was not found.',
            )
            ->willReturn($errorResponse);

        self::assertSame($errorResponse, $this->processRequest());
    }

    public function testRedirectsToTargetUrl(): void
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getUid')->willReturn(666);
        $tinyUrlMock->method('getTargetUrl')->willReturn('http://the-target.url');
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(false);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $response = $this->processRequest();
        self::assertSame(301, $response->getStatusCode());
        self::assertSame('Moved Permanently', $response->getReasonPhrase());
        self::assertSame('http://the-target.url', $response->getHeaderLine('Location'));
    }

    #[DataProvider('provideTinyUrlRedirectSendsNoCacheHeadersCases')]
    public function testTinyUrlRedirectSendsNoCacheHeaders(string $headerName, string $expectedValue): void
    {
        $_GET['tx_tinyurls']['key'] = 'thekey';
        $tinyUrlMock = $this->createMock(TinyUrl::class);
        $tinyUrlMock->method('getDeleteOnUse')->willReturn(true);

        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->willReturn($tinyUrlMock);

        $response = $this->processRequest();

        if ($expectedValue === 'gmdate') {
            $expectedValue = gmdate('D, d M Y H:i:s', $GLOBALS['EXEC_TIME']) . ' GMT';
        }

        self::assertSame($expectedValue, $response->getHeaderLine($headerName));
    }

    private function processRequest(): ResponseInterface
    {
        $request = new ServerRequest();
        $request = $request->withQueryParams($_GET);
        return $this->eidController->tinyUrlRedirect($request);
    }
}
