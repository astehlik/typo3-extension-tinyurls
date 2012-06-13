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
 */
class Tx_Tinyurls_Hooks_EidProcessor {

	/**
	 * Redirects the user to the target url if a valid tinyurl was
	 * submitted, otherwise the default 404 (not found) page is displayed
	 */
	public function main() {

		try {
			tslib_eidtools::connectDB();
			$this->purgeInvalidUrls();
			$targetUrl = $this->getTargetUrl();
			t3lib_utility_Http::redirect($targetUrl, t3lib_utility_http::HTTP_STATUS_301);
		} catch (Exception $exception) {
			/**
			 * @var $tsfe tslib_fe
			 */
			$tsfe = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], 0, 0);
			$tsfe->pageNotFoundAndExit($exception->getMessage());
		}
	}

	/**
	 * Returns the target URL that was found by the submitted tinyurl key
	 *
	 * @return string
	 * @throws RuntimeException If the target url can not be resolved
	 */
	protected function getTargetUrl() {

		$getVariables = t3lib_div::_GET('tx_tinyurls');
		$tinyUrlKey = NULL;

		if (is_array($getVariables) && array_key_exists('key', $getVariables)) {
			$tinyUrlKey = $getVariables['key'];
		} else {
			throw new RuntimeException('No tinyurl key was submitted.');
		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'urlkey,target_url,delete_on_use',
			'tx_tinyurls_urls',
			'urlkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tinyUrlKey, 'tx_tinyurls_urls')
		);

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
			throw new RuntimeException('The given tinyurl key was not found in the database.');
		}

		$tinyUrlData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

		if ($tinyUrlData['delete_on_use']) {

			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'tx_tinyurls_urls',
				'urlkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tinyUrlData['urlkey'], 'tx_tinyurls_urls')
			);

			$this->sendNoCacheHeaders();
		}

		return $tinyUrlData['target_url'];
	}

	/**
	 * Purges all invalid urls from the database
	 */
	protected function purgeInvalidUrls() {

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_tinyurls_urls',
				'valid_until>0 AND valid_until<' . time()
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