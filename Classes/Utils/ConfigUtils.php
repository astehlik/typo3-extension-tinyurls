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
 * Contains utilities for getting configuration
 */
class Tx_Tinyurls_Utils_ConfigUtils {

	/**
	 * Contains the default values for the extension configuration
	 *
	 * @var array
	 */
	protected static $extensionConfigurationDefaults = array(
		'createSpeakingURLs' => FALSE,
		'speakingUrlTemplate' => '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###',
		'base62Dictionary'  => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
		'minimalRandomKeyLength' => 2,
		'minimalTinyurlKeyLength' => 8,
	);

	/**
	 * Returns the extension configuration, merging the default config and the config
	 * defined by the user
	 *
	 * @return array
	 */
	public static function getExtensionConfiguration() {

		$extensionConfiguration = array();
		$finalConfiguration = array();

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tinyurls'])) {

			$extensionConfigurationData = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tinyurls']);

			if (is_array($extensionConfigurationData)) {
				$extensionConfiguration = $extensionConfigurationData;
			}
		}

		foreach (self::$extensionConfigurationDefaults as $configKey => $defaultValue) {

			if (array_key_exists($configKey, $extensionConfiguration)) {
				$finalConfiguration[$configKey] = $extensionConfiguration[$configKey];
			} else {
				$finalConfiguration[$configKey] = $defaultValue;
			}
		}

		return $finalConfiguration;
	}
}
