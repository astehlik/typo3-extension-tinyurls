<?php
declare(strict_types=1);

namespace Tx\Tinyurls\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Contains utilities for getting configuration
 */
class ExtensionConfiguration implements SingletonInterface
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
     * @var TypoScriptConfigurator
     */
    protected $typoScriptConfigurator;

    /**
     * Initializes the tinyurl configuration with default values and
     * if the user set his own values they are parsed through stdWrap
     *
     * @param array $config
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
     * @param \Tx\Tinyurls\TinyUrl\TinyUrlGenerator $tinyUrlGenerator
     * @deprecated Please use the TypoScriptConfigurator class instead.
     */
    public function initializeConfigFromTyposcript(
        array $config,
        ContentObjectRenderer $contentObject,
        TinyUrlGenerator $tinyUrlGenerator
    ) {
        $typoScriptConfigurator = $this->getTypoScriptConfigurator($tinyUrlGenerator);
        $typoScriptConfigurator->initializeConfigFromTyposcript($config, $contentObject);
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

        $whereStatement .= 'pid=' . (int)$this->getUrlRecordStoragePid();

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
     * @deprecated Please use the matching getter for retrieving a config value.
     * @return array
     */
    public function getExtensionConfiguration(): array
    {
        $this->initializeExtensionConfiguration();
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
        return (int)$this->getExtensionConfigurationValueInternal('urlRecordStoragePID');
    }

    public function setTypoScriptConfigurator(TypoScriptConfigurator $typoScriptConfigurator)
    {
        $this->typoScriptConfigurator = $typoScriptConfigurator;
    }

    protected function getExtensionConfigurationValueInternal(string $key)
    {
        $this->initializeExtensionConfiguration();

        if (!array_key_exists($key, $this->extensionConfiguration)) {
            throw new \InvalidArgumentException('The key ' . $key . ' does not exists in the extension configuration');
        }

        return $this->extensionConfiguration[$key];
    }

    /**
     * @param TinyUrlGenerator $tinyUrlGenerator
     * @return TypoScriptConfigurator
     * @codeCoverageIgnore
     */
    protected function getTypoScriptConfigurator(TinyUrlGenerator $tinyUrlGenerator): TypoScriptConfigurator
    {
        if ($this->typoScriptConfigurator === null) {
            $this->typoScriptConfigurator = GeneralUtility::makeInstance(
                TypoScriptConfigurator::class,
                $tinyUrlGenerator
            );
        }
        return $this->typoScriptConfigurator;
    }

    /**
     * Initializes the extension configuration array, merging the default config and the config
     * defined by the user
     */
    protected function initializeExtensionConfiguration()
    {
        if ($this->extensionConfiguration !== null) {
            return;
        }

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
