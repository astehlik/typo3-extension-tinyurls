<?php

class Tx_Tinyurls_Hooks_TypoLink {

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var array
	 */
	protected $tinyurlConfig = NULL;

	/**
	 * @var array
	 */
	protected $tinyurlConfigDefaults = array(
		'deleteOnUse' => 0,
		'validUntil' => 0,
		'urlKey' => FALSE,
	);

	/**
	 * @var int
	 */
	protected $createSpeakingURLs = 0;

	/**
	 * @var string
	 */
	protected $speakingUrlTemplate = '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###';

	/**
	 * All generated tinyurl keys will be at least that long
	 *
	 * @TODO make this configurable via extension manager
	 *
	 * @var int
	 */
	protected $minimalTinyurlKeyLength = 8;

	/**
	 * All generated tinyurl keys will at least contain this
	 * number of random letters
	 *
	 * @var int
	 */
	protected $minimalRandomKeyLength = 2;

	/**
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * This dictionary contains all letters from a-z, all capital letters A-Z
	 * and the numbers from 0 to 9
	 *
	 * @TODO make this settable via extension configuration and recommend the usage of http://textmechanic.com/String-Randomizer.html there
	 *
	 * @var string
	 */
	protected  $base62Dictionary  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	/**
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
	 * @param array $parameters array(
	 * 	'conf' => &$conf,
	 * 	'linktxt' => &$linktxt,
	 * 	'finalTag' => &$res,
	 * 	'finalTagParts' => &$finalTagParts
	 * );
	 * @param tslib_cObj $contentObject The parent content object
	 */
	public function createTinyUrl($parameters, $contentObject) {

		$config = $parameters['conf'];
		$finalTagParts = $parameters['finalTagParts'];

		if ($finalTagParts['TYPE'] === 'mailto') {
			return;
		}

		if (!(array_key_exists('tinyurl', $config) && $config['tinyurl'])) {
			return;
		}

		$targetUrl = $finalTagParts['url'];
		$tinyUrl = $this->getTinyUrl($targetUrl, $contentObject, $config);

		$parameters['finalTag'] = str_replace(htmlspecialchars($targetUrl), htmlspecialchars($tinyUrl), $parameters['finalTag']);
		$parameters['finalTagParts']['url'] = $tinyUrl;
		$contentObject->lastTypoLinkUrl = $tinyUrl;
	}

	/**
	 * @param string $targetUrl
	 * @param tslib_cObj $contentObject
	 * @param array $config
	 * @return string
	 */
	public function getTinyUrl($targetUrl, $contentObject, $config = array()) {

		$this->initializeExtensionConfiguration();
		$this->initializeTinyurlConfig($config, $contentObject);

		$targetUrlHash = sha1($targetUrl);

		$tinyUrlData = $this->getExistingTinyurl($targetUrlHash);
		if ($tinyUrlData === FALSE) {
			$tinyUrlData = $this->generateNewTinyurl($targetUrl, $targetUrlHash);
		}

		$tinyUrlKey = $tinyUrlData['urlkey'];
		if ($this->createSpeakingURLs) {
			$tinyUrl = $this->createSpeakingTinyUrl($tinyUrlKey);
		} else {
			$tinyUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
			$tinyUrl .= '?eID=tx_tinyurls&tx_tinyurls[key]=' . $tinyUrlKey;
		}

		return $tinyUrl;
	}

	/**
	 * Checks if there is already an existing tinyurl and returns its data
	 *
	 * @param $targetUrlHash
	 * @return bool|array FALSO if no existing URL was found, otherwise associative array with tinyurl data
	 */
	protected function getExistingTinyurl($targetUrlHash) {

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_tinyurls_urls', 'target_url_hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($targetUrlHash, 'tx_tinyurls_urls'));

		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
			return FALSE;
		} else {
			return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		}
	}

	/**
	 *
	 *
	 * @param string $targetUrl
	 * @param string $targetUrlHash
	 * @return array
	 */
	protected function generateNewTinyurl($targetUrl, $targetUrlHash) {

		$insertArray = array(
			'target_url' => $targetUrl,
			'target_url_hash' => $targetUrlHash,
			'delete_on_use' => $this->tinyurlConfig['deleteOnUse'],
			'valid_until' => $this->tinyurlConfig['validUntil'],
		);

		$customUrlKey = $this->getCustomUrlKey($targetUrlHash);
		if ($customUrlKey !== FALSE) {
			$insertArray['urlkey'] = $customUrlKey;
		}

		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'tx_tinyurls_urls',
			$insertArray
		);

		if ($customUrlKey === FALSE) {
			$insertedUid = $GLOBALS['TYPO3_DB']->sql_insert_id();
			$tinyUrlKey = $this->generateTinyurlKeyForUid($insertedUid);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_tinyurls_urls', 'uid=' . $insertedUid, array('urlkey' => $tinyUrlKey));
			$insertArray['urlkey'] = $tinyUrlKey;
		}

		return $insertArray;
	}

	/**
	 * Generates a unique tinyurl key for the record with the given UID
	 *
	 * @param $insertedUid
	 * @return array
	 */
	protected function generateTinyurlKeyForUid($insertedUid) {

		$tinyUrlKey = $this->convertIntToBase62($insertedUid);

		$numberOfFillupChars = $this->minimalTinyurlKeyLength - strlen($tinyUrlKey);

		if ($numberOfFillupChars < $this->minimalRandomKeyLength) {
			$numberOfFillupChars = $this->minimalRandomKeyLength;
		}

		if ($numberOfFillupChars < 1) {
			return $tinyUrlKey;
		}

		$tinyUrlKey .= '-' . t3lib_div::getRandomHexString($numberOfFillupChars);

		return $tinyUrlKey;
	}

	/**
	 * This mehtod converts the given base 10 integer to a base
	 * 62
	 *
	 * Thanks to http://jeremygibbs.com/2012/01/16/how-to-make-a-url-shortener
	 *
	 * @param $base10Integer
	 * @return string
	 */
	protected function convertIntToBase62($base10Integer) {

		$base62Integer = '';
		$base = 62;

		do
		{
			$base62Integer = $this->base62Dictionary[($base10Integer % $base)] . $base62Integer;
			$base10Integer = floor($base10Integer / $base);
		}
		while ($base10Integer > 0);

		return $base62Integer;
	}

	/**
	 * Initializes the tinyurl configuration with default values and
	 * if the user set his own values they are parsed through stdWrap
	 *
	 * @param array $config
	 * @param tslib_cObj $contentObject
	 */
	protected function initializeTinyurlConfig($config, $contentObject) {

		$this->contentObject = $contentObject;

		if (!array_key_exists('tinyurl.', $config)) {
			$this->tinyurlConfig = $this->tinyurlConfigDefaults;
			return;
		}

		$tinyUrlConfig = $config['tinyurl.'];
		$newTinyurlConfig = array();

		foreach ($this->tinyurlConfigDefaults as $configKey => $defaultValue) {

			$configValue = $defaultValue;

			if (array_key_exists($configKey, $tinyUrlConfig)) {

				$configValue = $tinyUrlConfig[$configKey];

				if (array_key_exists($configValue . '.', $tinyUrlConfig)) {
					$configValue = $contentObject->stdWrap($configValue, $tinyUrlConfig[$configKey . '.']);
				}
			}

			$newTinyurlConfig[$configKey] = $configValue;
		}

		$this->tinyurlConfig = $newTinyurlConfig;
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

		$customUrlKey = $this->tinyurlConfig['urlKey'];

		if ($customUrlKey === FALSE) {
			return FALSE;
		}

		if (empty($customUrlKey)) {
			throw new Exception('An empty url key was set.');
		}

		$customUrlKeyResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'target_url',
			'tx_tinyurls_urls',
				'urlkey=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($customUrlKey, 'tx_tinyurls_urls')
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
	 * Unserializes the extension configuration and loads it into
	 * the matching class variables
	 */
	protected function initializeExtensionConfiguration() {

		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tinyurls'])) {
			return;
		}

		$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tinyurls']);

		$this->createSpeakingURLs = $extensionConfiguration['createSpeakingURLs'];
		$this->speakingUrlTemplate = $extensionConfiguration['speakingUrlTemplate'];
	}

	/**
	 * Generates a speaking tinyurl based on the speaking url template
	 *
	 * @param $tinyUrlKey
	 * @return string
	 */
	protected function createSpeakingTinyUrl($tinyUrlKey) {

		$speakingUrl = $this->speakingUrlTemplate;

		foreach ($this->availableIndpEnvKeys as $indpEnvKey) {

			$templateMarker = '###' . strtoupper($indpEnvKey) . '###';

			if (strstr($speakingUrl, $templateMarker)) {
				$speakingUrl = $this->contentObject->substituteMarker($speakingUrl, $templateMarker, t3lib_div::getIndpEnv($indpEnvKey));
			}
		}

		$speakingUrl = $this->contentObject->substituteMarker($speakingUrl, '###TINY_URL_KEY###', $tinyUrlKey);

		return $speakingUrl;
	}
}


?>