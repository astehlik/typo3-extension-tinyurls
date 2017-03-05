<?php
declare(strict_types = 1);
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

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Object\ImplementationManager;
use Tx\Tinyurls\Utils\HttpUtilityWrapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Handles tiny URLs with the TYPO3 eID mechanism
 *
 * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
 * @author Sebastian Lemke <s.lemke.deleteme@infoworxx.de>
 */
class EidController
{
    /**
     * @var HttpUtilityWrapper
     */
    protected $httpUtility;

    /**
     * @var TinyUrlRepository
     */
    protected $tinyUrlRepository;

    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    /**
     * @param HttpUtilityWrapper $httpUtility
     */
    public function injectHttpUility(HttpUtilityWrapper $httpUtility)
    {
        $this->httpUtility = $httpUtility;
    }

    /**
     * @param TinyUrlRepository $tinyUrlRepository
     */
    public function injectTinyUrlRepository(TinyUrlRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    /**
     * @return TypoScriptFrontendController
     * @codeCoverageIgnore
     */
    public function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        if ($this->typoScriptFrontendController === null) {
            $this->typoScriptFrontendController = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                $GLOBALS['TYPO3_CONF_VARS'],
                0,
                0
            );
        }
        return $this->typoScriptFrontendController;
    }

    /**
     * Redirects the user to the target url if a valid tinyurl was
     * submitted, otherwise the default 404 (not found) page is displayed
     */
    public function main()
    {
        try {
            $this->getTinyUrlRepository()->purgeInvalidUrls();
            $tinyUrl = $this->getTinyUrl();
            $this->countUrlHit($tinyUrl);
            $this->getHttpUtility()->redirect($tinyUrl->getTargetUrl(), HttpUtility::HTTP_STATUS_301);
        } catch (\Exception $exception) {
            $tsfe = $this->getTypoScriptFrontendController();
            $tsfe->pageNotFoundAndExit($exception->getMessage());
        }
    }

    /**
     * @param TypoScriptFrontendController $typoScriptFrontendController
     */
    public function setTypoScriptFrontendController(TypoScriptFrontendController $typoScriptFrontendController)
    {
        $this->typoScriptFrontendController = $typoScriptFrontendController;
    }

    /**
     * Increases the hit counter for the given tiny URL record.
     *
     * @param TinyUrl $tinyUrl
     */
    protected function countUrlHit(TinyUrl $tinyUrl)
    {
        // There is no point in counting the hit of a URL that is already deleted
        if ($tinyUrl->getDeleteOnUse()) {
            return;
        }

        $this->getTinyUrlRepository()->countTinyUrlHit($tinyUrl);
    }

    /**
     * @return HttpUtilityWrapper
     * @codeCoverageIgnore
     */
    protected function getHttpUtility(): HttpUtilityWrapper
    {
        if ($this->httpUtility === null) {
            $this->httpUtility = GeneralUtility::makeInstance(HttpUtilityWrapper::class);
        }
        return $this->httpUtility;
    }

    /**
     * Returns the data of the tiny URL record that was found by the submitted tinyurl key.
     *
     * @return TinyUrl
     * @throws \RuntimeException If the target url can not be resolved
     */
    protected function getTinyUrl(): TinyUrl
    {
        $getVariables = GeneralUtility::_GET();
        if (empty($getVariables['tx_tinyurls']['key'])) {
            throw new \RuntimeException('No tinyurl key was submitted.');
        }

        $tinyUrlKey = (string)$getVariables['tx_tinyurls']['key'];

        $tinyUrl = $this->getTinyUrlRepository()->findTinyUrlByKey($tinyUrlKey);

        if ($tinyUrl->getDeleteOnUse()) {
            $this->getTinyUrlRepository()->deleteTinyUrlByKey($tinyUrlKey);
            $this->sendNoCacheHeaders();
        }

        return $tinyUrl;
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

    /**
     * Sends headers that the user does not cache the page
     */
    protected function sendNoCacheHeaders()
    {
        $httpUtility = $this->getHttpUtility();
        $httpUtility->header('Expires', '0');
        $httpUtility->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $httpUtility->header('Cache-Control', 'no-cache, must-revalidate');
        $httpUtility->header('Pragma', 'no-cache');
    }
}
