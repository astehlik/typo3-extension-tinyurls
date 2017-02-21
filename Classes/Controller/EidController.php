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

use Tx\Tinyurls\Domain\Repository\TinyUrlDatabaseRepository;
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
     * @var TinyUrlDatabaseRepository
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
     * @param TinyUrlDatabaseRepository $tinyUrlRepository
     */
    public function injectTinyUrlRepository(TinyUrlDatabaseRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    /**
     * @return TypoScriptFrontendController
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
            $tinyUrlData = $this->getTinyUrlData();
            $this->countUrlHit($tinyUrlData);
            $this->getHttpUtility()->redirect($tinyUrlData['target_url'], HttpUtility::HTTP_STATUS_301);
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
     * @param array $tinyUrlData
     */
    protected function countUrlHit($tinyUrlData)
    {
        // There is no point in counting the hit of a URL that is already deleted
        if ($tinyUrlData['delete_on_use']) {
            return;
        }

        $this->getTinyUrlRepository()->countTinyUrlHit((int)$tinyUrlData['uid']);
    }

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
     * @return array
     * @throws \RuntimeException If the target url can not be resolved
     */
    protected function getTinyUrlData()
    {
        $getVariables = GeneralUtility::_GET();
        if (empty($getVariables['tx_tinyurls']['key'])) {
            throw new \RuntimeException('No tinyurl key was submitted.');
        }

        $tinyUrlKey = (string)$getVariables['tx_tinyurls']['key'];

        $tinyUrlData = $this->getTinyUrlRepository()->findTinyUrlByKey($tinyUrlKey);

        if ($tinyUrlData['delete_on_use']) {
            $this->getTinyUrlRepository()->deleteTinyUrlByKey($tinyUrlKey);
            $this->sendNoCacheHeaders();
        }

        return $tinyUrlData;
    }

    protected function getTinyUrlRepository(): TinyUrlDatabaseRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = GeneralUtility::makeInstance(TinyUrlDatabaseRepository::class);
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
