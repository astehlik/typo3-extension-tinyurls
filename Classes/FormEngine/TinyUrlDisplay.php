<?php
declare(strict_types = 1);
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
class TinyUrlDisplay
{
    /**
     * @var TinyUrlGenerator
     */
    protected $tinyUrlGenerator;

    /**
     * Renders a full tiny URL based on the given form element data.
     *
     * This method is called as a valueFunc by the TYPO3 form engine.
     *
     * @param array $formElementData
     * @return string
     */
    public function buildTinyUrlFormFormElementData(array $formElementData): string
    {
        $tinyUrlGenerator = $this->getTinyUrlGenerator();
        return $tinyUrlGenerator->buildTinyUrl($formElementData['databaseRow']['urlkey']);
    }

    /**
     * @param TinyUrlGenerator $tinyUrlGenerator
     */
    public function setTinyUrlGenerator(TinyUrlGenerator $tinyUrlGenerator)
    {
        $this->tinyUrlGenerator = $tinyUrlGenerator;
    }

    protected function getTinyUrlGenerator(): TinyUrlGenerator
    {
        if ($this->tinyUrlGenerator === null) {
            $this->tinyUrlGenerator = GeneralUtility::makeInstance(TinyUrlGenerator::class);
        }
        return $this->tinyUrlGenerator;
    }
}
