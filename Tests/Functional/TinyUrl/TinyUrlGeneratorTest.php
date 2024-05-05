<?php

declare(strict_types=1);

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

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Tests\Functional\AbstractFunctionalTestCase;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional tests for the TinyUrlGenerator.
 */
class TinyUrlGeneratorTest extends AbstractFunctionalTestCase
{
    protected TinyUrlGenerator $tinyUrlGenerator;

    /**
     * Initializes the test subject.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tinyUrlGenerator = GeneralUtility::makeInstance(TinyUrlGenerator::class);
    }

    public function testGetTinyUrlSetsTstampOfNewTinyUrl(): void
    {
        $this->tinyUrlGenerator->generateTinyUrl(TinyUrl::createForUrl('http://mydomain.tld'));
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertGreaterThanOrEqual($GLOBALS['EXEC_TIME'], $tinyUrlRow['tstamp']);
    }
}
