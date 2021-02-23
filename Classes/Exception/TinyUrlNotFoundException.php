<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Exception;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Error\Http\PageNotFoundException;

class TinyUrlNotFoundException extends PageNotFoundException
{
    public function __construct(string $tinyUrlKey = '')
    {
        parent::__construct($this->buildMessage($tinyUrlKey));
    }

    private function buildMessage(string $tinyUrlKey)
    {
        if ($tinyUrlKey !== '') {
            return sprintf('The tinyurl with the key %s was not found.', $tinyUrlKey);
        }

        return 'The tinyurl was not found.';
    }
}
