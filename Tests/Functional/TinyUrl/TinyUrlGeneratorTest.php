<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Functional\TinyUrl;

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
use TYPO3\Components\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for the TinyUrlGenerator.
 */
class TinyUrlGeneratorTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/tinyurls'];

    /**
     * @var TinyUrlGenerator
     */
    protected $tinyUrlGenerator;

    /**
     * Initializes the test subject.
     */
    public function setUp()
    {
        parent::setUp();
        $this->tinyUrlGenerator = GeneralUtility::makeInstance(TinyUrlGenerator::class);
    }

    /**
     * @test
     */
    public function getTinyUrlSetsTstampOfNewTinyUrl()
    {
        $this->tinyUrlGenerator->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'tstamp',
            'tx_tinyurls_urls',
            'uid=1'
        );
        $this->assertGreaterThanOrEqual($GLOBALS['EXEC_TIME'], $tinyUrlRow['tstamp']);
    }
}
