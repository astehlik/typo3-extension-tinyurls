<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Functional;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Nimut\TestingFramework\TestCase\FunctionalTestCase;

/**
 * Functional tests for the tinyurls API.
 */
class TypoScriptTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/tinyurls'];

    /**
     * Imports the pages database fixture.
     */
    public function setUp()
    {
        parent::setUp();
        if (file_exists(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml')) {
            $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
        } else {
            // For TYPO3 master master
            $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/frontend/Tests/Functional/Fixtures/pages.xml');
        }
    }

    /**
     * @test
     */
    public function typolinkIsConvertedToTinyurlIfConfigured()
    {
        $this->setUpFrontendRootPage(
            1,
            ['EXT:tinyurls/Tests/Functional/Fixtures/TypoScript/SimpleTinyUrlTypolink.setupts']
        );
        $response = $this->getFrontendResponse(0);
        $this->assertRegExp(
            '/http:\/\/localhost\/\?eID=tx_tinyurls&amp;tx_tinyurls\[key\]=b-[a-zA-Z0-9]{7}/',
            $response->getContent()
        );
    }
}
