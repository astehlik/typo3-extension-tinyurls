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
 * Contains utilities for creating tiny url keys and url hashes
 */
class Tx_Tinyurls_Utils_UrlUtils implements t3lib_Singleton {

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
	 * This mehtod converts the given base 10 integer to a base62
	 *
	 * Thanks to http://jeremygibbs.com/2012/01/16/how-to-make-a-url-shortener
	 *
	 * @param int $base10Integer The integer that will converted
	 * @param string $base62Dictionary the dictionary for generating the base62 integer
	 * @return string A base62 encoded integer using a custom dictionary
	 */
	protected function convertIntToBase62($base10Integer, $base62Dictionary) {

		$base62Integer = '';
		$base = 62;

		do
		{
			$base62Integer = $base62Dictionary[($base10Integer % $base)] . $base62Integer;
			$base10Integer = floor($base10Integer / $base);
		}
		while ($base10Integer > 0);

		return $base62Integer;
	}

	/**
	 * Generates a unique tinyurl key for the record with the given UID
	 *
	 * @param int $insertedUid
	 * @return array
	 */
	public function generateTinyurlKeyForUid($insertedUid) {

		$tinyUrlKey = $this->convertIntToBase62($insertedUid, $this->configUtils->getExtensionConfigurationValue('base62Dictionary'));

		$numberOfFillupChars = $this->configUtils->getExtensionConfigurationValue('minimalTinyurlKeyLength') - strlen($tinyUrlKey);

		if ($numberOfFillupChars < $this->configUtils->getExtensionConfigurationValue('minimalRandomKeyLength')) {
			$numberOfFillupChars = $this->configUtils->getExtensionConfigurationValue('minimalRandomKeyLength');
		}

		if ($numberOfFillupChars < 1) {
			return $tinyUrlKey;
		}

		$tinyUrlKey .= '-' . t3lib_div::getRandomHexString($numberOfFillupChars);

		return $tinyUrlKey;
	}

	/**
	 * Generates a sha1 hash of the given URL
	 *
	 * @param string $url
	 * @return string
	 */
	public function generateTinyurlHash($url) {
		return sha1($url);
	}
}
