<?php
namespace Tx\Tinyurls\Hooks;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Utils\ConfigUtils;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Handles tiny URLs with the TYPO3 eID mechanism
 *
 * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
 * @author Sebastian Lemke <s.lemke.deleteme@infoworxx.de>
 */
class EidProcessor
{
    /**
     * Contains the extension configration
     *
     * @var \Tx\Tinyurls\Utils\ConfigUtils
     */
    protected $configUtils;

    /**
     * Initializes the extension configuration
     */
    public function __construct()
    {
        $this->configUtils = GeneralUtility::makeInstance(ConfigUtils::class);
    }

    /**
     * Redirects the user to the target url if a valid tinyurl was
     * submitted, otherwise the default 404 (not found) page is displayed
     */
    public function main()
    {
        try {
            $this->purgeInvalidUrls();
            $tinyUrlData = $this->getTinyUrlData();
            $this->countUrlHit($tinyUrlData);
            HttpUtility::redirect($tinyUrlData['target_url'], HttpUtility::HTTP_STATUS_301);
        } catch (\Exception $exception) {
            $tsfe = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                $GLOBALS['TYPO3_CONF_VARS'],
                0,
                0
            );
            $tsfe->pageNotFoundAndExit($exception->getMessage());
        }
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

        // See: http://lists.typo3.org/pipermail/typo3-dev/2007-December/026936.html
        // Use of "set counter=counter+1" - avoiding race conditions
        $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_tinyurls_urls',
            'uid=' . (integer)$tinyUrlData['uid'],
            array('counter' => 'counter + 1'),
            array('counter')
        );
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Returns the data of the tiny URL record that was found by the submitted tinyurl key.
     *
     * @return array
     * @throws \RuntimeException If the target url can not be resolved
     */
    protected function getTinyUrlData()
    {
        $getVariables = GeneralUtility::_GET('tx_tinyurls');
        $tinyUrlKey = null;

        if (is_array($getVariables) && array_key_exists('key', $getVariables)) {
            $tinyUrlKey = $getVariables['key'];
        } else {
            throw new \RuntimeException('No tinyurl key was submitted.');
        }

        $selctWhereStatement = 'urlkey=' .
            $this->getDatabaseConnection()->fullQuoteStr($tinyUrlKey, 'tx_tinyurls_urls');
        $selctWhereStatement = $this->configUtils->appendPidQuery($selctWhereStatement);

        $result = $this->getDatabaseConnection()->exec_SELECTquery(
            'uid,urlkey,target_url,delete_on_use',
            'tx_tinyurls_urls',
            $selctWhereStatement
        );

        if (!$this->getDatabaseConnection()->sql_num_rows($result)) {
            throw new \RuntimeException('The given tinyurl key was not found in the database.');
        }

        $tinyUrlData = $this->getDatabaseConnection()->sql_fetch_assoc($result);

        if ($tinyUrlData['delete_on_use']) {
            $deleteWhereStatement = 'urlkey=' .
                $this->getDatabaseConnection()->fullQuoteStr($tinyUrlData['urlkey'], 'tx_tinyurls_urls');
            $deleteWhereStatement = $this->configUtils->appendPidQuery($deleteWhereStatement);

            $this->getDatabaseConnection()->exec_DELETEquery(
                'tx_tinyurls_urls',
                $deleteWhereStatement
            );

            $this->sendNoCacheHeaders();
        }

        return $tinyUrlData;
    }

    /**
     * Purges all invalid urls from the database
     */
    protected function purgeInvalidUrls()
    {
        $purgeWhereStatement = 'valid_until>0 AND valid_until<' . time();
        $purgeWhereStatement = $this->configUtils->appendPidQuery($purgeWhereStatement);

        $this->getDatabaseConnection()->exec_DELETEquery(
            'tx_tinyurls_urls',
            $purgeWhereStatement
        );
    }

    /**
     * Sends headers that the user does not cache the page
     */
    protected function sendNoCacheHeaders()
    {
        header('Expires: 0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
}
