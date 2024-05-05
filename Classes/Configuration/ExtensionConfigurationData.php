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

/**
 * Contains utilities for getting configuration.
 */
readonly class ExtensionConfigurationData
{
    /**
     * @param non-negative-int $urlRecordStoragePid
     */
    protected function __construct(
        /** @extensionScannerIgnoreLine */
        public string $baseUrl,
        public bool $baseUrlFromSiteBase,
        public bool $createSpeakingUrls,
        public string $base62Dictionary,
        public int $minimalRandomKeyLength,
        public int $minimalTinyurlKeyLength,
        public string $speakingUrlTemplate,
        public int $urlRecordStoragePid,
    ) {}

    public static function fromArray(array $extensionConfiguration): self
    {
        $defaults = new ExtensionConfigurationDefaults();
        $extensionConfiguration = array_merge($defaults->toArray(), $extensionConfiguration);

        return new self(
            (string)$extensionConfiguration[ConfigKeys::BASE_URL],
            (bool)$extensionConfiguration[ConfigKeys::BASE_URL_FROM_SITE_BASE],
            (bool)$extensionConfiguration[ConfigKeys::CREATE_SPEAKING_URLS],
            (string)$extensionConfiguration[ConfigKeys::BASE62_DICTIONARY],
            (int)$extensionConfiguration[ConfigKeys::MINIMAL_RANDOM_KEY_LENGTH],
            (int)$extensionConfiguration[ConfigKeys::MINIMAL_TINYURL_KEY_LENGTH],
            (string)$extensionConfiguration[ConfigKeys::SPEAKING_URL_TEMPLATE],
            max(0, (int)$extensionConfiguration[ConfigKeys::URL_RECORD_STORAGE_PID]),
        );
    }

    private function toArray(): array
    {
        return [
            ConfigKeys::BASE_URL => $this->baseUrl,
            ConfigKeys::BASE_URL_FROM_SITE_BASE => $this->baseUrlFromSiteBase,
            ConfigKeys::CREATE_SPEAKING_URLS => $this->createSpeakingUrls,
            ConfigKeys::BASE62_DICTIONARY => $this->base62Dictionary,
            ConfigKeys::MINIMAL_RANDOM_KEY_LENGTH => $this->minimalRandomKeyLength,
            ConfigKeys::MINIMAL_TINYURL_KEY_LENGTH => $this->minimalTinyurlKeyLength,
            ConfigKeys::SPEAKING_URL_TEMPLATE => $this->speakingUrlTemplate,
            ConfigKeys::URL_RECORD_STORAGE_PID => $this->urlRecordStoragePid,
        ];
    }
}
