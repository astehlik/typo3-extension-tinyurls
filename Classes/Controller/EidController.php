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
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\NoTinyUrlKeySubmittedException;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Object\ImplementationManager;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Handles tiny URLs with the TYPO3 eID mechanism
 *
 * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
 * @author Sebastian Lemke <s.lemke.deleteme@infoworxx.de>
 */
class EidController
{
    /**
     * @var TinyUrlRepository
     */
    protected $tinyUrlRepository;

    /**
     * @var ErrorController
     */
    private $errorController;

    /**
     * @param TinyUrlRepository $tinyUrlRepository
     */
    public function injectTinyUrlRepository(TinyUrlRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    public function setErrorController(ErrorController $errorController): void
    {
        $this->errorController = $errorController;
    }

    public function tinyUrlRedirect(ServerRequestInterface $request): ResponseInterface
    {
        $this->getTinyUrlRepository()->purgeInvalidUrls();

        try {
            $tinyUrl = $this->getTinyUrl($request);
        } catch (TinyUrlNotFoundException $e) {
            return $this->handleTinyUrlNotFoundError($request, $e);
        }

        $this->processUrlHit($tinyUrl);

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
            gmdate('D, d M Y H:i:s', $GLOBALS['EXEC_TIME']) . ' GMT'
        );
        $noCacheResponse = $noCacheResponse->withAddedHeader('Cache-Control', 'no-cache, must-revalidate');
        $noCacheResponse = $noCacheResponse->withAddedHeader('Pragma', 'no-cache');
        return $noCacheResponse;
    }

    /**
     * Increases the hit counter for the given tiny URL record.
     *
     * @param TinyUrl $tinyUrl
     */
    protected function countUrlHit(TinyUrl $tinyUrl): void
    {
        // There is no point in counting the hit of a URL that is already deleted
        if ($tinyUrl->getDeleteOnUse()) {
            return;
        }

        $this->getTinyUrlRepository()->countTinyUrlHit($tinyUrl);
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
     * @param ServerRequestInterface $request
     * @return TinyUrl
     * @throws TinyUrlNotFoundException
     */
    protected function getTinyUrl(ServerRequestInterface $request): TinyUrl
    {
        $queryParams = $request->getQueryParams();
        if (empty($queryParams['tx_tinyurls']['key'])) {
            throw new NoTinyUrlKeySubmittedException();
        }

        $tinyUrlKey = (string)$queryParams['tx_tinyurls']['key'];

        return $this->getTinyUrlRepository()->findTinyUrlByKey($tinyUrlKey);
    }

    /**
     * @return TinyUrlRepository
     * @codeCoverageIgnore
     */
    protected function getTinyUrlRepository(): TinyUrlRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = ImplementationManager::getInstance()->getTinyUrlRepository();
        }
        return $this->tinyUrlRepository;
    }

    protected function handleTinyUrlNotFoundError(
        ServerRequestInterface $request,
        TinyUrlNotFoundException $e
    ): ResponseInterface {
        $errorController = $this->getErrorController();
        return $errorController->pageNotFoundAction($request, $e->getMessage());
    }

    protected function processUrlHit(TinyUrl $tinyUrl): void
    {
        if ($tinyUrl->getDeleteOnUse()) {
            $this->getTinyUrlRepository()->deleteTinyUrlByKey($tinyUrl->getUrlkey());
            return;
        }

        $this->countUrlHit($tinyUrl);
    }
}
