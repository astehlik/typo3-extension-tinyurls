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
     * Imports the pages database fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/pages.csv');
    }

    public function testTypolinkIsConvertedToTinyurlIfConfigured(): void
    {
        $this->setUpFrontendRootPage(
            1,
            ['EXT:tinyurls/Tests/Functional/Fixtures/TypoScript/SimpleTinyUrlTypolink.setupts']
        );
        $this->setUpFrontendSite(1);
        $request = (new InternalRequest())->withPageId(1);
        $response = $this->executeFrontendSubRequest($request);
        self::assertMatchesRegularExpression(
            '/http:\/\/localhost\/\?eID=tx_tinyurls&amp;tx_tinyurls\[key\]=b-[a-zA-Z0-9]{7}/',
            (string)$response->getBody()
        );
    }
}
