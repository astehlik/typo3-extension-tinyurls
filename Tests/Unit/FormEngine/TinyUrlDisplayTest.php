<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\FormEngine;

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
use Tx\Tinyurls\FormEngine\TinyUrlDisplay;
use Tx\Tinyurls\Utils\UrlUtilsInterface;

/**
 * Builds the tiny URL for displaying it within Backend forms.
 */
class TinyUrlDisplayTest extends TestCase
{
    public function testBuildTinyUrlFormFormElementDataBuildTinyUrlUsingUrlKey(): void
    {
        $urlUtilsMock = $this->createMock(UrlUtilsInterface::class);
        $urlUtilsMock->expects(self::once())
            ->method('buildTinyUrlForPid')
            ->with('the tiny urlkey', 12)
            ->willReturn('the generated url');

        $tinyUrlDisplay = new TinyUrlDisplay($urlUtilsMock);

        $formElementData['databaseRow']['pid'] = '12';
        $formElementData['databaseRow']['urlkey'] = 'the tiny urlkey';
        self::assertSame('the generated url', $tinyUrlDisplay->buildTinyUrlFormFormElementData($formElementData));
    }
}
