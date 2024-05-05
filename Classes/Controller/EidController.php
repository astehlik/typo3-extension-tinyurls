<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\NoTinyUrlKeySubmittedException;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Handles tiny URLs with the TYPO3 eID mechanism.
 *
 * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
 * @author Sebastian Lemke <s.lemke.deleteme@infoworxx.de>
 */
class EidController
{
    private ?ErrorController $errorController = null;

    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly SiteMatcher $siteMatcher,
        protected readonly TinyUrlRepository $tinyUrlRepository,
    ) {}

    public function setErrorController(ErrorController $errorController): void
    {
        $this->errorController = $errorController;
    }

    public function tinyUrlRedirect(ServerRequestInterface $request): ResponseInterface
    {
        $this->extensionConfiguration->setSite($this->getSiteFromRequest($request));

        $this->tinyUrlRepository->purgeInvalidUrls();

        try {
            $tinyUrl = $this->getTinyUrl($request);
        } catch (TinyUrlNotFoundException $e) {
            return $this->handleTinyUrlNotFoundError($request, $e);
        }

        $this->processUrlHit($tinyUrl);

        $this->extensionConfiguration->reset();

        $response = new Response();
        $noCacheResponse = $this->addNoCacheHeaders($response);

        $redirectResponse = $noCacheResponse->withStatus(301);
        return $redirectResponse->withAddedHeader('Location', $tinyUrl->getTargetUrl());
    }

    protected function addNoCacheHeaders(ResponseInterface $response): ResponseInterface
    {
        $noCacheResponse = $response->withAddedHeader('Expires', '0');
        $noCacheResponse = $noCacheResponse->withAddedHeader(
            'Last-Modified',
            gmdate('D, d M Y H:i:s', $GLOBALS['EXEC_TIME']) . ' GMT',
        );
        $noCacheResponse = $noCacheResponse->withAddedHeader('Cache-Control', 'no-cache, must-revalidate');
        return $noCacheResponse->withAddedHeader('Pragma', 'no-cache');
    }

    /**
     * Increases the hit counter for the given tiny URL record.
     */
    protected function countUrlHit(TinyUrl $tinyUrl): void
    {
        // There is no point in counting the hit of a URL that is already deleted
        if ($tinyUrl->getDeleteOnUse()) {
            return;
        }

        $this->tinyUrlRepository->countTinyUrlHit($tinyUrl);
    }

    protected function getErrorController(): ErrorController
    {
        if ($this->errorController) {
            return $this->errorController;
        }

        return GeneralUtility::makeInstance(ErrorController::class);
    }

    /**
     * Returns the data of the tiny URL record that was found by the submitted tinyurl key.
     *
     * @throws TinyUrlNotFoundException
     */
    protected function getTinyUrl(ServerRequestInterface $request): TinyUrl
    {
        $queryParams = $request->getQueryParams();
        if (empty($queryParams['tx_tinyurls']['key'])) {
            throw new NoTinyUrlKeySubmittedException();
        }

        $tinyUrlKey = (string)$queryParams['tx_tinyurls']['key'];

        return $this->tinyUrlRepository->findTinyUrlByKey($tinyUrlKey);
    }

    protected function handleTinyUrlNotFoundError(
        ServerRequestInterface $request,
        TinyUrlNotFoundException $e,
    ): ResponseInterface {
        $errorController = $this->getErrorController();
        return $errorController->pageNotFoundAction($request, $e->getMessage());
    }

    protected function processUrlHit(TinyUrl $tinyUrl): void
    {
        if ($tinyUrl->getDeleteOnUse()) {
            $this->tinyUrlRepository->deleteTinyUrlByKey($tinyUrl->getUrlkey());
            return;
        }

        $this->countUrlHit($tinyUrl);
    }

    private function getSiteFromRequest(ServerRequestInterface $request): ?SiteInterface
    {
        $result =  $this->siteMatcher->matchRequest($request);

        if (!$result instanceof SiteRouteResult) {
            return null;
        }

        // @extensionScannerIgnoreLine
        return $result->getSite();
    }
}
