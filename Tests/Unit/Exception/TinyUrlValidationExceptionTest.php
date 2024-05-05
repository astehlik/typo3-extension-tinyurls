<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Exception;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Exception\TinyUrlValidationException;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;

class TinyUrlValidationExceptionTest extends TestCase
{
    public function testSetInitializesFullErrorMessage(): void
    {
        $result = new Result();
        $result->forProperty('targetUrl')->addError(new Error('The URL is invalid', 123));
        $validationException = new TinyUrlValidationException();
        $validationException->setValidationResult($result);
        self::assertSame('The given tiny URL data is invalid: The URL is invalid', $validationException->getMessage());
    }

    public function testSetResultSetsResult(): void
    {
        $result = new Result();
        $validationException = new TinyUrlValidationException();
        $validationException->setValidationResult($result);
        self::assertSame($result, $validationException->getResult());
    }
}
