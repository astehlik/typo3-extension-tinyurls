<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Unit\Utils;

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
use Tx\Tinyurls\Utils\CompatibilityWrapper;

class CompatibilityWrapperTest extends TestCase
{
    /**
     * @var CompatibilityWrapper
     */
    protected $compatibilityWrapper;

    protected function setUp()
    {
        $this->compatibilityWrapper = new CompatibilityWrapper();
    }

    public function testGetExtensionPathPrefixForTcaIconfileReturnsExtStringForNewerTypo3Version()
    {
        $this->compatibilityWrapper->setTypo3Version('7.6.0');
        $this->assertEquals('EXT:tinyurls/', $this->compatibilityWrapper->getExtensionPathPrefixForTcaIconfile());
    }

    /**
     * @backupGlobals enable
     */
    public function testGetExtensionPathPrefixForTcaIconfileUsesExtRelPathForOlderTypo3Versions()
    {
        $this->expectException(\Error::class);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog'] = false;
        $this->compatibilityWrapper->setTypo3Version('7.5.0');
        $this->assertEquals('EXT:tinyurls/', $this->compatibilityWrapper->getExtensionPathPrefixForTcaIconfile());
    }
}
