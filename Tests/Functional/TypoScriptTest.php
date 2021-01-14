<?php
declare(strict_types=1);

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

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Functional tests for the tinyurls API.
 */
class TypoScriptTest extends AbstractFunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/tinyurls'];

    /**
     * Imports the pages database fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/Fixtures/Database/pages.xml');
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
        $request = (new InternalRequest())->withPageId(1);
        $response = $this->executeFrontendRequest($request);
        $this->assertMatchesRegularExpression(
            '/http:\/\/localhost\/\?eID=tx_tinyurls&amp;tx_tinyurls\[key\]=b-[a-zA-Z0-9]{7}/',
            (string)$response->getBody()
        );
    }
}
