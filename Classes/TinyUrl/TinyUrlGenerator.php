<?php
/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This class is responsible for generating tiny Urls based on configuration
 * options and extension configuration
 */
class Tx_Tinyurls_TinyUrl_TinyUrlGenerator {

	/**
	 * Contains the configuration that can be set in the extension manager
	 *
	 * @var Tx_Tinyurls_Utils_ConfigUtils
	 */
	protected $configUtils;

	/**
	 * If this option is 1 the URL will be deleted from the database
	 * on the first hit
	 *
	 * @var bool
	 */
	var $optionDeleteOnUse =  0;

	/**
	 * With this option the user can specify a custom URL key
	 *
	 * @var bool
	 */
	var $optionUrlKey = FALSE;

	/**
	 * If this value is set to a timestamp the URL will be invalid
	 * after this timestamp has passed.
	 *
	 * @var int
	 */
	var $optionValidUntil = 0;

	/**
	 * Tiny URL utilities
	 *
	 * @var Tx_Tinyurls_Utils_UrlUtils
	 */
	var $urlUtils;

	/**
	 * Initializes the config utils
	 */
	public function __construct() {
		$this->configUtils = t3lib_div::makeInstance('Tx_Tinyurls_Utils_ConfigUtils');
		$this->urlUtils = t3lib_div::makeInstance('Tx_Tinyurls_Utils_UrlUtils');
	}

	/**
	 * This method generates a tiny URL, stores it in the database
	 * and returns the full URL
	 *
	 * @param string $targetUrl The URL that should be minified
	 * @return string The generated tinyurl
	 */
	public function getTinyUrl($targetUrl) {

		if (empty($targetUrl)) {
			return $targetUrl;
		}

		$targetUrlHash = $this->urlUtils->generateTinyurlHash($targetUrl);

		$tinyUrlData = $this->getExistingTinyurl($targetUrlHash);
		if ($tinyUrlData === FALSE) {
			$tinyUrlData = $this->generateNewTinyurl($targetUrl, $targetUrlHash);
		}

		$tinyUrlKey = $tinyUrlData['urlkey'];
		if ($this->configUtils->getExtensionConfigurationValue('createSpeakingURLs')) {
			$tinyUrl = $this->createSpeakingTinyUrl($tinyUrlKey);
		} else {
			$tinyUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
			$tinyUrl .= '?eID=tx_tinyurls&tx_tinyurls[key]=' . $tinyUrlKey;
		}

		return $tinyUrl;
	}

	/**
	 * Sets the deleteOnUse option, if 1 the URL will be deleted from
	 * the database on the first hit
	 *
	 * @param int $deleteOnUse
	 */
	public function setOptionDeleteOnUse($deleteOnUse) {
		$this->optionDeleteOnUse = intval($deleteOnUse);
	}

	/**
	 * Sets a custom URL key, must be unique
	 *
	 * @param string $urlKey
	 */
	public function setOptionUrlKey($urlKey) {

		if (!empty($urlKey)) {
			$this->optionUrlKey = $urlKey;
		} else {
			$this->optionUrlKey = FALSE;
		}
	}

	/**
	 * Sets the timestamp until the generated URL is valid
	 *
	 * @param int $validUntil
	 */
	public function setOptionValidUntil($validUntil) {
		$this->optionValidUntil = intval($validUntil);
	}

	/**
	 * Generates a speaking tinyurl based on the speaking url template
	 *
	 * @param $tinyUrlKey
	 * @return string
	 */
	protected function createSpeakingTinyUrl($tinyUrlKey) {

		$speakingUrl = $this->configUtils->getExtensionConfigurationValue('speakingUrlTemplate');

		foreach ($this->configUtils->getAvailableIndpEnvKeys() as $indpEnvKey) {

			$templateMarker = '###' . strtoupper($indpEnvKey) . '###';

			if (strstr($speakingUrl, $templateMarker)) {
				$speakingUrl = t3lib_parsehtml::substituteMarker($speakingUrl, $templateMarker, t3lib_div::getIndpEnv($indpEnvKey));
			}
		}

		$speakingUrl = t3lib_parsehtml::substituteMarker($speakingUrl, '###TINY_URL_KEY###', $tinyUrlKey);

		return $speakingUrl;
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
	protected function generateNewTinyurl($targetUrl, $targetUrlHash) {

		$insertArray = array(
			'pid' => $this->configUtils->getExtensionConfigurationValue('urlRecordStoragePID'),
			'target_url' => $targetUrl,
			'target_url_hash' => $targetUrlHash,
			'delete_on_use' => $this->optionDeleteOnUse,
			'valid_until' => $this->optionValidUntil,
		);

		$customUrlKey = $this->getCustomUrlKey($targetUrlHash);
		if ($customUrlKey !== FALSE) {
			$insertArray['urlkey'] = $customUrlKey;
		}

		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_tinyurls_urls',
			$insertArray
		);

		// if no custom URL key was set, the key is generated using the
		// uid from the database
		if ($customUrlKey === FALSE) {
			$insertedUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
			$tinyUrlKey = $this->urlUtils->generateTinyurlKeyForUid($insertedUid);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_tinyurls_urls', 'uid=' . $insertedUid, array('urlkey' => $tinyUrlKey));
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
	 * @throws Exception If custom url key was set but empty or if the key already existed with a different URL
	 */
	protected function getCustomUrlKey($targetUrlHash) {

		$customUrlKey = $this->optionUrlKey;

		if ($customUrlKey === FALSE) {
			return FALSE;
		}

		if (empty($customUrlKey)) {
			throw new Exception('An empty url key was set.');
		}

		$customUrlKeyWhere = 'urlkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($customUrlKey, 'tx_tinyurls_urls');
		$customUrlKeyWhere = $this->configUtils->appendPidQuery($customUrlKeyWhere);

		$customUrlKeyResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'target_url',
			'tx_tinyurls_urls',
			$customUrlKeyWhere
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($customUrlKeyResult)) {

			$existingUrlData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($customUrlKeyResult);

			if ($existingUrlData['target_url_hash'] !== $targetUrlHash) {
				throw new Exception('A url key was set that already exists in the database and points to a different URL.');
			}
		}

		return $customUrlKey;
	}

	/**
	 * Checks if there is already an existing tinyurl and returns its data
	 *
	 * @param $targetUrlHash
	 * @return bool|array FALSE if no existing URL was found, otherwise associative array with tinyurl data
	 */
	protected function getExistingTinyurl($targetUrlHash) {

		$whereStatement = 'target_url_hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($targetUrlHash, 'tx_tinyurls_urls');
		$whereStatement = $this->configUtils->appendPidQuery($whereStatement);

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_tinyurls_urls',
			$whereStatement
		);

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
			return FALSE;
		} else {
			return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		}
	}
}

?>