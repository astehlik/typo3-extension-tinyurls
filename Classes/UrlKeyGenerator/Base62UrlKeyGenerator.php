<?php

declare(strict_types=1);

namespace Tx\Tinyurls\UrlKeyGenerator;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains utilities for creating tiny url keys and url hashes.
 */
class Base62UrlKeyGenerator implements UrlKeyGenerator
{
    public function __construct(
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly GeneralUtilityWrapper $generalUtility
    ) {
    }

    /**
     * Generates a unique tinyurl key for the record with the given UID.
     */
    public function generateTinyurlKeyForTinyUrl(TinyUrl $tinyUrl): string
    {
        return $this->generateTinyurlKeyForUid($tinyUrl->getUid());
    }

    /**
     * Generates a unique tinyurl key for the given UID.
     */
    public function generateTinyurlKeyForUid(int $uid): string
    {
        $tinyUrlKey = $this->convertIntToBase62(
            $uid,
            $this->extensionConfiguration->getBase62Dictionary()
        );

        $numberOfFillupChars =
            $this->extensionConfiguration->getMinimalTinyurlKeyLength() - strlen($tinyUrlKey);

        $minimalRandomKeyLength = $this->extensionConfiguration->getMinimalRandomKeyLength();
        if ($numberOfFillupChars < $minimalRandomKeyLength) {
            $numberOfFillupChars = $minimalRandomKeyLength;
        }

        if ($numberOfFillupChars < 1) {
            return $tinyUrlKey;
        }

        $tinyUrlKey .= '-' . $this->generalUtility->getRandomHexString($numberOfFillupChars);

        return $tinyUrlKey;
    }

    /**
     * This mehtod converts the given base 10 integer to a base62.
     *
     * Thanks to http://jeremygibbs.com/2012/01/16/how-to-make-a-url-shortener
     *
     * @param int $base10Integer The integer that will converted
     * @param string $base62Dictionary the dictionary for generating the base62 integer
     *
     * @return string A base62 encoded integer using a custom dictionary
     */
    protected function convertIntToBase62(int $base10Integer, string $base62Dictionary): string
    {
        $base62Integer = '';
        $base = 62;

        do {
            $base62Integer = $base62Dictionary[$base10Integer % $base] . $base62Integer;
            $base10Integer = floor($base10Integer / $base);
        } while ($base10Integer > 0);

        return $base62Integer;
    }
}
