<?php
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

use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Builds the tiny URL for displaying it within Backend forms.
 */
class TinyUrlDispay
{
    /**
     * Renders a full tiny URL based on the given form element data.
     *
     * @param array $formElementData
     * @return string
     */
    public function buildTinyUrlFormFormElementData(array $formElementData)
    {
        $tinyUrlGenerator = GeneralUtility::makeInstance(TinyUrlGenerator::class);
        return $tinyUrlGenerator->buildTinyUrl($formElementData['databaseRow']['urlkey']);
    }
}
