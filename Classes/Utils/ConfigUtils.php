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
class Tx_Tinyurls_Utils_ConfigUtils implements t3lib_Singleton {

	/**
	 * Contains all valid keys for t3lib_div::getIndpEnv()
	 *
	 * @var array
	 */
	protected $availableIndpEnvKeys = array(
		'REQUEST_URI',
		'HTTP_HOST',
		'SCRIPT_NAME',
		'PATH_INFO',
		'QUERY_STRING',
		'HTTP_REFERER',
		'REMOTE_ADDR',
		'REMOTE_HOST',
		'HTTP_USER_AGENT',
		'HTTP_ACCEPT_LANGUAGE',
		'SCRIPT_FILENAME',
		'TYPO3_HOST_ONLY',
		'TYPO3_PORT',
		'TYPO3_REQUEST_HOST',
		'TYPO3_REQUEST_URL',
		'TYPO3_REQUEST_SCRIPT',
		'TYPO3_REQUEST_DIR',
		'TYPO3_SITE_URL',
		'TYPO3_SITE_PATH',
		'TYPO3_SITE_SCRIPT',
		'TYPO3_DOCUMENT_ROOT',
		'TYPO3_SSL',
		'TYPO3_PROXY',
	);

	/**
	 * Contains the default values for the extension configuration
	 *
	 * @var array
	 */
	protected $extensionConfigurationDefaults = array(
		'createSpeakingURLs' => FALSE,
		'speakingUrlTemplate' => '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###',
		'base62Dictionary'  => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
		'minimalRandomKeyLength' => 2,
		'minimalTinyurlKeyLength' => 8,
		'urlRecordStoragePID' => 0,
	);

	/**
	 * The initialized extension configuration
	 *
	 * @var array
	 */
	protected $extensionConfiguration = NULL;

	/**
	 * Contains the default values for the tinyurl configuration
	 *
	 * @var array
	 */
	protected $tinyurlConfigDefaults = array(
		'deleteOnUse' => 0,
		'validUntil' => 0,
		'urlKey' => FALSE,
	);

	/**
	 * Initializes the extension configuration array, merging the default config and the config
	 * defined by the user
	 */
	protected function initializeExtensionConfiguration() {

		$extensionConfiguration = array();
		$finalConfiguration = array();

		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tinyurls'])) {

			$extensionConfigurationData = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tinyurls']);

			if (is_array($extensionConfigurationData)) {
				$extensionConfiguration = $extensionConfigurationData;
			}
		}

		foreach ($this->extensionConfigurationDefaults as $configKey => $defaultValue) {

			if (array_key_exists($configKey, $extensionConfiguration)) {
				$finalConfiguration[$configKey] = $extensionConfiguration[$configKey];
			} else {
				$finalConfiguration[$configKey] = $defaultValue;
			}
		}

		$this->extensionConfiguration = $finalConfiguration;
	}

	/**
	 * Appends a PID query to the given where statement
	 *
	 * @param string $whereStatement The where statement where the PID query should be appended to
	 * @return string The where statement with the appended PID query
	 */
	public function appendPidQuery($whereStatement) {

		if (!empty($whereStatement)) {
			$whereStatement .= ' AND ';
		}

		$whereStatement .= 'pid=' . intval($this->getExtensionConfigurationValue('urlRecordStoragePID'));

		return $whereStatement;
	}

	/**
	 * Returns all valid keys for t3lib_div::getIndpEnv()
	 *
	 * @return array
	 */
	public function getAvailableIndpEnvKeys() {
		return $this->availableIndpEnvKeys;
	}

	/**
	 * Returns the extension configuration
	 *
	 * @return array
	 */
	public function getExtensionConfiguration() {

		if ($this->extensionConfiguration === NULL) {
			$this->initializeExtensionConfiguration();
		}

		return $this->extensionConfiguration;
	}

	/**
	 * Returns an extension configuration value
	 *
	 * @param string $key the configuration key
	 * @return mixed the configuration value
	 * @throws InvalidArgumentException if the configuration key does not exist
	 */
	public function getExtensionConfigurationValue($key) {

		if ($this->extensionConfiguration === NULL) {
			$this->initializeExtensionConfiguration();
		}

		if (!array_key_exists($key, $this->extensionConfiguration)) {
			throw new InvalidArgumentException('The key ' . $key . ' does not exists in the extension configuration');
		}

		return $this->extensionConfiguration[$key];
	}

	/**
	 * Initializes the tinyurl configuration with default values and
	 * if the user set his own values they are parsed through stdWrap
	 *
	 * @param array $config
	 * @param tslib_cObj $contentObject
	 * @param Tx_Tinyurls_TinyUrl_TinyUrlGenerator $tinyUrlGenerator
	 */
	public function initializeConfigFromTyposcript($config, $contentObject, $tinyUrlGenerator) {

		if (!array_key_exists('tinyurl.', $config)) {
			return;
		}

		$tinyUrlConfig = $config['tinyurl.'];

		foreach ($this->tinyurlConfigDefaults as $configKey => $defaultValue) {

			$configValue = $defaultValue;

			if (array_key_exists($configKey, $tinyUrlConfig)) {

				$configValue = $tinyUrlConfig[$configKey];

				if (array_key_exists($configValue . '.', $tinyUrlConfig)) {
					$configValue = $contentObject->stdWrap($configValue, $tinyUrlConfig[$configKey . '.']);
				}
			}

			$configSetter = 'set' . ucfirst($configKey);
			$tinyUrlGenerator->$configSetter($configValue);
		}
	}
}

?>