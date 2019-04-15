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
use Tx\Tinyurls\Utils\UrlUtils;

/**
 * Builds the tiny URL for displaying it within Backend forms.
 */
class TinyUrlDisplayTest extends TestCase
{
    public function testBuildTinyUrlFormFormElementDataBuildTinyUrlUsingUrlKey()
    {
        $tinyUrlDisplay = new TinyUrlDisplay();

        /** @var UrlUtils|\PHPUnit_Framework_MockObject_MockObject $tinyUrlGenerator */
        $tinyUrlGenerator = $this->createMock(UrlUtils::class);
        $tinyUrlGenerator->expects($this->once())
            ->method('buildTinyUrl')
            ->with('the tiny urlkey')
            ->willReturn('the generated url');
        $tinyUrlDisplay->injectUrlUtils($tinyUrlGenerator);

        $formElementData['databaseRow']['urlkey'] = 'the tiny urlkey';
        $this->assertEquals('the generated url', $tinyUrlDisplay->buildTinyUrlFormFormElementData($formElementData));
    }
}
