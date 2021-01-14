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

use Tx\Tinyurls\Domain\Model\TinyUrl;

interface UrlKeyGenerator
{
    /**
     * Generates a unique tinyurl key for the record with the given UID.
     *
     * @param TinyUrl $tinyUrl
     * @return string
     */
    public function generateTinyurlKeyForTinyUrl(TinyUrl $tinyUrl): string;

    /**
     * Generates a unique tinyurl key for the given UID.
     *
     * @param int $uid
     * @return string
     */
    public function generateTinyurlKeyForUid(int $uid): string;
}
