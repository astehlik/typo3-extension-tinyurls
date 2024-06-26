<?php

declare(strict_types=1);

namespace Tx\Tinyurls\FormEngine;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Utils\UrlUtilsInterface;

/**
 * Builds the tiny URL for displaying it within Backend forms.
 */
readonly class TinyUrlDisplay
{
    public function __construct(
        private UrlUtilsInterface $urlUtils,
    ) {}

    /**
     * Renders a full tiny URL based on the given form element data.
     *
     * This method is called as a valueFunc by the TYPO3 form engine.
     */
    public function buildTinyUrlFormFormElementData(array $formElementData): string
    {
        return $this->urlUtils->buildTinyUrlForPid(
            $formElementData['databaseRow']['urlkey'],
            (int)$formElementData['databaseRow']['pid'],
        );
    }
}
