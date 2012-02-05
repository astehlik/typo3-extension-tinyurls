<?php

class Tx_Tinyurls_Hooks_EidProcessor {

	/**
	 * @internal $tsfe tslib_fe
	 */
	public function main() {

		try {
			$targetUrl = $this->getTargetUrl();
			t3lib_utility_Http::redirect($targetUrl, t3lib_utility_http::HTTP_STATUS_301);
		} catch (Exception $exception) {
			$tsfe = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], 0, 0);

				//@TODO: Make the error handling configurable in the extension configuration
			$tsfe->pageNotFoundAndExit($exception->getMessage());
		}
	}

	protected function getTargetUrl() {

		$getVariables = t3lib_div::_GET('tx_tinyurls');
		$tinyUrlKey = NULL;

		if (is_array($getVariables) && array_key_exists('key', $getVariables)) {
			$tinyUrlKey = $getVariables['key'];
		} else {
			throw new RuntimeException('No tinyurl key was submitted.');
		}

		tslib_eidtools::connectDB();

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'urlkey,target_url,delete_on_use',
			'tx_tinyurls_urls',
			'urlkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tinyUrlKey, 'tx_tinyurls_urls') .
			' AND valid_until >= ' . time()
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
	 * Sends headers that the user does not cache the page
	 */
	protected function sendNoCacheHeaders() {
		header('Expires: 0');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
	}
}

$eidProcessor  = t3lib_div::makeInstance('tx_tinyurls_hooks_eidprocessor');
$eidProcessor->main();

?>