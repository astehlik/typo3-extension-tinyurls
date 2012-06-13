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
class Tx_Tinyurls_Utils_UrlUtils {

	/**
	 * This mehtod converts the given base 10 integer to a base62
	 *
	 * Thanks to http://jeremygibbs.com/2012/01/16/how-to-make-a-url-shortener
	 *
	 * @param int $base10Integer The integer that will converted
	 * @param string $base62Dictionary the dictionary for generating the base62 integer
	 * @return string A base62 encoded integer using a custom dictionary
	 */
	protected static function convertIntToBase62($base10Integer, $base62Dictionary) {

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
	 * @param array $extensionConfiguration
	 * @return array
	 */
	public static function generateTinyurlKeyForUid($insertedUid, $extensionConfiguration) {

		$tinyUrlKey = self::convertIntToBase62($insertedUid, $extensionConfiguration['base62Dictionary']);

		$numberOfFillupChars = $extensionConfiguration['minimalTinyurlKeyLength'] - strlen($tinyUrlKey);

		if ($numberOfFillupChars < $extensionConfiguration['minimalRandomKeyLength']) {
			$numberOfFillupChars = $extensionConfiguration['minimalRandomKeyLength'];
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
	public static function generateTinyurlHash($url) {
		return sha1($url);
	}
}
