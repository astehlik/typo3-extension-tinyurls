<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Extbase\Error\Result;

class TinyUrlValidationExceptionTest extends TestCase
{

    public function testSetResultSetsResult()
    {
        $result = new Result();
        $validationException = new TinyUrlValidationException();
        $validationException->setValidationResult($result);
        $this->assertEquals($result, $validationException->getResult());
    }
}
