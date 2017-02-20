<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\TinyUrl;

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
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class is responsible for generating tiny Urls based on configuration
 * options and extension configuration
 */
class TinyUrlGenerator
{
    /**
     * @var string
     */
    protected $comment = '';

    /**
     * Contains the configuration that can be set in the extension manager
     *
     * @var ConfigUtils
     */
    protected $configUtils;

    /**
     * If this option is 1 the URL will be deleted from the database
     * on the first hit
     *
     * @var bool
     */
    protected $optionDeleteOnUse = false;

    /**
     * With this option the user can specify a custom URL key
     *
     * @var bool
     */
    protected $optionUrlKey = false;

    /**
     * If this value is set to a timestamp the URL will be invalid
     * after this timestamp has passed.
     *
     * @var int
     */
    protected $optionValidUntil = 0;

    /**
     * Tiny URL utilities
     *
     * @var UrlUtils
     */
    protected $urlUtils;

    /**
     * Initializes the config utils
     */
    public function __construct()
    {
        $this->configUtils = GeneralUtility::makeInstance(ConfigUtils::class);
        $this->urlUtils = GeneralUtility::makeInstance(UrlUtils::class);
    }

    /**
     * Builds a complete tiny URL based on the given URL key and the createSpeakingURLs setting.
     *
     * @param string $tinyUrlKey
     * @return string
     */
    public function buildTinyUrl($tinyUrlKey): string
    {
        if ($this->configUtils->getExtensionConfigurationValue('createSpeakingURLs')) {
            $tinyUrl = $this->urlUtils->createSpeakingTinyUrl($tinyUrlKey);
            return $tinyUrl;
        } else {
            $tinyUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            $tinyUrl .= '?eID=tx_tinyurls&tx_tinyurls[key]=' . $tinyUrlKey;
            return $tinyUrl;
        }
    }

    /**
     * This method generates a tiny URL, stores it in the database
     * and returns the full URL
     *
     * @param string $targetUrl The URL that should be minified
     * @return string The generated tinyurl
     */
    public function getTinyUrl($targetUrl)
    {
        if (empty($targetUrl)) {
            return $targetUrl;
        }

        $targetUrlHash = $this->urlUtils->generateTinyurlHash($targetUrl);

        $tinyUrlData = $this->getExistingTinyurl($targetUrlHash);
        if ($tinyUrlData === false) {
            $tinyUrlData = $this->generateNewTinyurl($targetUrl, $targetUrlHash);
        }

        $tinyUrl = $this->buildTinyUrl($tinyUrlData['urlkey']);

        return $tinyUrl;
    }

    /**
     * Sets the comment for the next tinyurl that is generated.
     *
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = (string)$comment;
    }

    /**
     * Sets the deleteOnUse option, if 1 the URL will be deleted from
     * the database on the first hit
     *
     * @param bool $deleteOnUse
     */
    public function setOptionDeleteOnUse($deleteOnUse)
    {
        $this->optionDeleteOnUse = (bool)$deleteOnUse;
    }

    /**
     * Sets a custom URL key, must be unique
     *
     * @param string $urlKey
     */
    public function setOptionUrlKey($urlKey)
    {
        if (!empty($urlKey)) {
            $this->optionUrlKey = $urlKey;
        } else {
            $this->optionUrlKey = false;
        }
    }

    /**
     * Sets the timestamp until the generated URL is valid
     *
     * @param int $validUntil
     */
    public function setOptionValidUntil($validUntil)
    {
        $this->optionValidUntil = intval($validUntil);
    }

    /**
     * Inserts a new record in the database
     *
     * Does not check, if the url hash already exists! This is done in
     * getTinyUrl().
     *
     * @param string $targetUrl
     * @param string $targetUrlHash
     * @return array
     */
    protected function generateNewTinyurl($targetUrl, $targetUrlHash)
    {
        $insertArray = [
            'pid' => $this->configUtils->getExtensionConfigurationValue('urlRecordStoragePID'),
            'target_url' => $targetUrl,
            'target_url_hash' => $targetUrlHash,
            'delete_on_use' => (int)$this->optionDeleteOnUse,
            'valid_until' => $this->optionValidUntil,
            'comment' => $this->comment,
            'tstamp' => $GLOBALS['EXEC_TIME'],
        ];

        $customUrlKey = $this->getCustomUrlKey($targetUrlHash);
        if ($customUrlKey !== false) {
            $insertArray['urlkey'] = $customUrlKey;
            $insertArray['urldisplay'] = $this->urlUtils->createSpeakingTinyUrl($customUrlKey);
        }

        $this->getDatabaseConnection()->exec_INSERTquery(
            'tx_tinyurls_urls',
            $insertArray
        );

        // If no custom URL key was set, the key is generated using the uid from the database.
        if ($customUrlKey === false) {
            $insertedUid = $this->getDatabaseConnection()->sql_insert_id();
            $tinyUrlKey = $this->urlUtils->generateTinyurlKeyForUid($insertedUid);
            $this->getDatabaseConnection()->exec_UPDATEquery(
                'tx_tinyurls_urls',
                'uid=' . $insertedUid,
                ['urlkey' => $tinyUrlKey]
            );
            $insertArray['urlkey'] = $tinyUrlKey;
        }

        return $insertArray;
    }

    /**
     * Checks the tinyurl config and returns a custom tinyurl key if
     * one was set
     *
     * @param string $targetUrlHash The target url hash is needed to check if the custom key matches the target url
     * @return bool|string FALSE if no custom key was set, otherwise the custom key
     * @throws \Exception If custom url key was set but empty or if the key already existed with a different URL
     */
    protected function getCustomUrlKey($targetUrlHash)
    {
        $customUrlKey = $this->optionUrlKey;

        if ($customUrlKey === false) {
            return false;
        }

        if (empty($customUrlKey)) {
            throw new \Exception('An empty url key was set.');
        }

        $customUrlKeyWhere = 'urlkey=' .
            $this->getDatabaseConnection()->fullQuoteStr($customUrlKey, 'tx_tinyurls_urls');
        $customUrlKeyWhere = $this->configUtils->appendPidQuery($customUrlKeyWhere);

        $customUrlKeyResult = $this->getDatabaseConnection()->exec_SELECTquery(
            'target_url',
            'tx_tinyurls_urls',
            $customUrlKeyWhere
        );

        if ($this->getDatabaseConnection()->sql_num_rows($customUrlKeyResult)) {
            $existingUrlData = $this->getDatabaseConnection()->sql_fetch_assoc($customUrlKeyResult);

            if ($existingUrlData['target_url_hash'] !== $targetUrlHash) {
                throw new \Exception(
                    'A url key was set that already exists in the database and points to a different URL.'
                );
            }
        }

        return $customUrlKey;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Checks if there is already an existing tinyurl and returns its data
     *
     * @param $targetUrlHash
     * @return bool|array FALSE if no existing URL was found, otherwise associative array with tinyurl data
     */
    protected function getExistingTinyurl($targetUrlHash)
    {
        $whereStatement = 'target_url_hash=' .
            $this->getDatabaseConnection()->fullQuoteStr($targetUrlHash, 'tx_tinyurls_urls');
        $whereStatement = $this->configUtils->appendPidQuery($whereStatement);

        $result = $this->getDatabaseConnection()->exec_SELECTquery(
            '*',
            'tx_tinyurls_urls',
            $whereStatement
        );

        if (!$this->getDatabaseConnection()->sql_num_rows($result)) {
            return false;
        } else {
            return $this->getDatabaseConnection()->sql_fetch_assoc($result);
        }
    }
}
