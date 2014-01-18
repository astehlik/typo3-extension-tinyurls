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
 * Handles tiny URLs with the TYPO3 eID mechanism
 *
 * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
 * @author Sebastian Lemke <s.lemke.deleteme@infoworxx.de>
 */
class Tx_Tinyurls_Hooks_EidProcessor {

	/**
	 * Contains the extension configration
	 *
	 * @var Tx_Tinyurls_Utils_ConfigUtils
	 */
	var $configUtils;

	/**
	 * Initializes the extension configuration
	 */
	public function __construct() {
		$this->configUtils = t3lib_div::makeInstance('Tx_Tinyurls_Utils_ConfigUtils');
	}

	/**
	 * Redirects the user to the target url if a valid tinyurl was
	 * submitted, otherwise the default 404 (not found) page is displayed
	 */
	public function main() {

		try {
			tslib_eidtools::connectDB();
			$this->purgeInvalidUrls();
			$tinyUrlData = $this->getTinyUrlData();
			$this->countUrlHit($tinyUrlData);
			t3lib_utility_Http::redirect($tinyUrlData['target_url'], t3lib_utility_http::HTTP_STATUS_301);
		} catch (Exception $exception) {
			/**
			 * @var $tsfe tslib_fe
			 */
			$tsfe = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], 0, 0);
			$tsfe->pageNotFoundAndExit($exception->getMessage());
		}
	}

	/**
	 * Increases the hit counter for the given tiny URL record.
	 *
	 * @param array $tinyUrlData
	 */
	protected function countUrlHit($tinyUrlData) {

		// There is no point in counting the hit of a URL that is already deleted
		if ($tinyUrlData['delete_on_use']) {
			return;
		}

		// http://lists.typo3.org/pipermail/typo3-dev/2007-December/026936.html
		// Use of "set counter=counter+1" - avoiding race conditions
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_tinyurls_urls',
			'uid=' . (integer)$tinyUrlData['uid'],
			array('counter' => 'counter + 1'),
			array('counter')
		);
	}

	/**
	 * Returns the data of the tiny URL record that was found by the submitted tinyurl key.
	 *
	 * @return array
	 * @throws RuntimeException If the target url can not be resolved
	 */
	protected function getTinyUrlData() {

		$getVariables = t3lib_div::_GET('tx_tinyurls');
		$tinyUrlKey = NULL;

		if (is_array($getVariables) && array_key_exists('key', $getVariables)) {
			$tinyUrlKey = $getVariables['key'];
		} else {
			throw new RuntimeException('No tinyurl key was submitted.');
		}

		$selctWhereStatement = 'urlkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tinyUrlKey, 'tx_tinyurls_urls');
		$selctWhereStatement = $this->configUtils->appendPidQuery($selctWhereStatement);

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid,urlkey,target_url,delete_on_use',
			'tx_tinyurls_urls',
			$selctWhereStatement
		);

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
			throw new RuntimeException('The given tinyurl key was not found in the database.');
		}

		$tinyUrlData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

		if ($tinyUrlData['delete_on_use']) {

			$deleteWhereStatement = 'urlkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tinyUrlData['urlkey'], 'tx_tinyurls_urls');
			$deleteWhereStatement = $this->configUtils->appendPidQuery($deleteWhereStatement);

			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
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
	protected function purgeInvalidUrls() {

		$purgeWhereStatement = 'valid_until>0 AND valid_until<' . time();
		$purgeWhereStatement = $this->configUtils->appendPidQuery($purgeWhereStatement);

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_tinyurls_urls',
			$purgeWhereStatement
		);
	}

	/**
	 * Sends headers that the user does not cache the page
	 */
	protected function sendNoCacheHeaders() {
		header('Expires: 0');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
	}
}

/**
 * @var Tx_Tinyurls_Hooks_EidProcessor $eidProcessor
 */
$eidProcessor  = t3lib_div::makeInstance('tx_tinyurls_hooks_eidprocessor');
$eidProcessor->main();

?>