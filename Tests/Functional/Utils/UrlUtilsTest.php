<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional\Utils;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Configuration\ConfigKeys;
use Tx\Tinyurls\Tests\Functional\AbstractFunctionalTestCase;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional tests for the tinyurls API.
 */
class UrlUtilsTest extends AbstractFunctionalTestCase
{
    public function testCreateSpeakingTinyUrlReplacesGeneralUtilityMarkers(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['tinyurls'][ConfigKeys::SPEAKING_URL_TEMPLATE] = '###REMOTE_ADDR###';
        $urlUtils = GeneralUtility::makeInstance(UrlUtils::class);
        self::assertSame('127.0.0.1', $urlUtils->createSpeakingTinyUrl('test'));
    }
}
