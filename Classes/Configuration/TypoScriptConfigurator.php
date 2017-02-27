<?php
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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TypoScriptConfigurator
{
    /**
     * @var TinyUrlGenerator
     */
    protected $tinyUrlGenerator;

    /**
     * protected $contentObjectRenderer;
     *
     * /**
     * Contains the default values for the tinyurl configuration
     *
     * @var array
     */
    protected $tinyurlConfigDefaults = [
        'deleteOnUse' => 0,
        'validUntil' => 0,
        'urlKey' => false,
    ];

    public function __construct(TinyUrlGenerator $tinyUrlGenerator)
    {
        $this->tinyUrlGenerator = $tinyUrlGenerator;
    }

    /**
     * Initializes the tinyurl configuration with default values and
     * if the user set his own values they are parsed through stdWrap
     *
     * @param array $config
     * @param ContentObjectRenderer $contentObjectRenderer
     */
    public function initializeConfigFromTyposcript(array $config, ContentObjectRenderer $contentObjectRenderer)
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
                    $configValue = $contentObjectRenderer->stdWrap(
                        $configValue,
                        $tinyUrlConfig[$configKey . '.']
                    );
                }
            }

            $configSetter = 'setOption' . ucfirst($configKey);

            if (method_exists($this->tinyUrlGenerator, $configSetter)) {
                $this->tinyUrlGenerator->$configSetter($configValue);
            }
        }
    }
}
