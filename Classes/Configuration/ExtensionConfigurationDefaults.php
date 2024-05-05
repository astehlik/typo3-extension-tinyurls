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

readonly class ExtensionConfigurationDefaults extends ExtensionConfigurationData
{
    public function __construct()
    {
        parent::__construct(
            '',
            false,
            false,
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            2,
            8,
            '###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###',
            0,
        );
    }
}
