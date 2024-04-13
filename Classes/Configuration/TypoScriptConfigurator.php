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

use Tx\Tinyurls\Domain\Model\TinyUrl;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use DateTimeImmutable;

class TypoScriptConfigurator
{
    /**
     * Contains the default values for the tinyurl configuration.
     */
    protected array $tinyurlConfigDefaults = [
        'deleteOnUse' => 0,
        'validUntil' => 0,
        'urlKey' => false,
    ];

    /**
     * Initializes the tinyurl configuration with default values and
     * if the user set his own values they are parsed through stdWrap.
     */
    public function initializeConfigFromTyposcript(
        TinyUrl $tinyUrl,
        array $config,
        ContentObjectRenderer $contentObjectRenderer,
    ): void {
        if (!array_key_exists('tinyurl.', $config)) {
            return;
        }

        foreach (array_keys($this->tinyurlConfigDefaults) as $configKey) {
            $configValue = $this->getConfigValue($configKey, $config['tinyurl.'], $contentObjectRenderer);

            match ($configKey) {
                'deleteOnUse' => $this->setOptionDeleteOnUse($tinyUrl, (bool)$configValue),
                'validUntil' => $this->setOptionValidUntil($tinyUrl, (int)$configValue),
                'urlKey' => $this->setOptionUrlKey($tinyUrl, (string)$configValue),
            };
        }
    }

    /**
     * @internal Only used for backwards compatibility. Will become private with next major version.
     */
    public function setOptionDeleteOnUse(TinyUrl $tinyUrl, bool $deleteOnUse): void
    {
        if (!$deleteOnUse) {
            $tinyUrl->disableDeleteOnUse();
            return;
        }

        $tinyUrl->enableDeleteOnUse();
    }

    /**
     * @internal Only used for backwards compatibility. Will become private with next major version.
     */
    public function setOptionUrlKey(TinyUrl $tinyUrl, string $urlKey): void
    {
        if ($urlKey === '') {
            $tinyUrl->resetCustomUrlKey();
            return;
        }

        $tinyUrl->setCustomUrlKey($urlKey);
    }

    /**
     * @internal Only used for backwards compatibility. Will become private with next major version.
     */
    public function setOptionValidUntil(TinyUrl $tinyUrl, int $validUntil): void
    {
        if ($validUntil <= 0) {
            $tinyUrl->resetValidUntil();
            return;
        }

        $tinyUrl->setValidUntil(new DateTimeImmutable('@' . $validUntil));
    }

    private function getConfigValue(
        string $configKey,
        array $tinyUrlConfig,
        ContentObjectRenderer $contentObjectRenderer,
    ): mixed {
        $configValue = $this->tinyurlConfigDefaults[$configKey];

        if (!array_key_exists($configKey, $tinyUrlConfig)) {
            return $configValue;
        }

        $configValue = $tinyUrlConfig[$configKey];

        if (!array_key_exists($configKey . '.', $tinyUrlConfig)) {
            return $configValue;
        }

        return $contentObjectRenderer->stdWrap($configValue, $tinyUrlConfig[$configKey . '.']);
    }
}
