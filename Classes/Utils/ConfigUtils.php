<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Utils;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Contains utilities for getting configuration
 */
class ConfigUtils implements SingletonInterface
{
    /**
     * The initialized extension configuration
     *
     * @var array
     */
    protected $extensionConfiguration = null;

    /**
     * Contains the default values for the extension configuration
     *
     * @var array
     */
    protected $extensionConfigurationDefaults = [
        'createSpeakingURLs' => false,
        'speakingUrlTemplate' => '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###',
        'base62Dictionary' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'minimalRandomKeyLength' => 2,
        'minimalTinyurlKeyLength' => 8,
        'urlRecordStoragePID' => 0,
    ];

    /**
     * Contains the default values for the tinyurl configuration
     *
     * @var array
     */
    protected $tinyurlConfigDefaults = [
        'deleteOnUse' => 0,
        'validUntil' => 0,
        'urlKey' => false,
    ];

    /**
     * Initializes the tinyurl configuration with default values and
     * if the user set his own values they are parsed through stdWrap
     *
     * @param array $config
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
     * @param \Tx\Tinyurls\TinyUrl\TinyUrlGenerator $tinyUrlGenerator
     */
    public function initializeConfigFromTyposcript($config, $contentObject, $tinyUrlGenerator)
    {
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

            $configSetter = 'setOption' . ucfirst($configKey);

            if (method_exists($tinyUrlGenerator, $configSetter)) {
                $tinyUrlGenerator->$configSetter($configValue);
            }
        }
    }

    /**
     * Appends a PID query to the given where statement
     *
     * @param string $whereStatement The where statement where the PID query should be appended to
     * @return string The where statement with the appended PID query
     */
    public function appendPidQuery($whereStatement)
    {
        if (!empty($whereStatement)) {
            $whereStatement .= ' AND ';
        }

        $whereStatement .= 'pid=' . intval($this->getExtensionConfigurationValueInternal('urlRecordStoragePID'));

        return $whereStatement;
    }

    public function areSpeakingUrlsEnabled(): bool
    {
        return (bool)$this->getExtensionConfigurationValueInternal('createSpeakingURLs');
    }

    public function getBase62Dictionary(): string
    {
        return (string)$this->getExtensionConfigurationValueInternal('base62Dictionary');
    }

    /**
     * Returns the extension configuration
     *
     * @return array
     */
    public function getExtensionConfiguration()
    {
        if ($this->extensionConfiguration === null) {
            $this->initializeExtensionConfiguration();
        }

        return $this->extensionConfiguration;
    }

    /**
     * Returns an extension configuration value
     *
     * @deprecated Please use the matching getter for retrieving the config value.
     * @param string $key the configuration key
     * @return mixed the configuration value
     * @throws \InvalidArgumentException if the configuration key does not exist
     */
    public function getExtensionConfigurationValue(string $key)
    {
        return $this->getExtensionConfigurationValueInternal($key);
    }

    public function getMinimalRandomKeyLength(): int
    {
        return (int)$this->getExtensionConfigurationValueInternal('minimalRandomKeyLength');
    }

    public function getMinimalTinyurlKeyLength(): int
    {
        return (int)$this->getExtensionConfigurationValueInternal('minimalTinyurlKeyLength');
    }

    public function getSpeakingUrlTemplate(): string
    {
        return (string)$this->getExtensionConfigurationValueInternal('speakingUrlTemplate');
    }

    public function getUrlRecordStoragePid(): int
    {
        return $this->getExtensionConfigurationValueInternal('urlRecordStoragePID');
    }

    protected function getExtensionConfigurationValueInternal(string $key)
    {
        if ($this->extensionConfiguration === null) {
            $this->initializeExtensionConfiguration();
        }

        if (!array_key_exists($key, $this->extensionConfiguration)) {
            throw new \InvalidArgumentException('The key ' . $key . ' does not exists in the extension configuration');
        }

        return $this->extensionConfiguration[$key];
    }

    /**
     * Initializes the extension configuration array, merging the default config and the config
     * defined by the user
     */
    protected function initializeExtensionConfiguration()
    {
        $extensionConfiguration = [];
        $finalConfiguration = [];

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
}
