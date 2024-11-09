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

use InvalidArgumentException;
use TYPO3\CMS\Extbase\Error\Result;

class TinyUrlValidationException extends InvalidArgumentException
{
    protected Result $result;

    public function __construct()
    {
        parent::__construct('The given tiny URL data is invalid.', 1529430222);
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public function setValidationResult(Result $result): void
    {
        $this->result = $result;

        $errorMessages = [];
        foreach ($result->getFlattenedErrors() as $errors) {
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
        }

        $this->message = 'The given tiny URL data is invalid: ' . implode(', ', $errorMessages);
    }
}
