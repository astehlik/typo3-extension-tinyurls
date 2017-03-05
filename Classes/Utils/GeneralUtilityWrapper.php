<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Utils;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A wrapper class for GeneralUtility calls. This allows us better mocking in the Unit tests.
 *
 * @codeCoverageIgnore
 */
class GeneralUtilityWrapper implements SingletonInterface
{
    public function callUserFunction(string $funcName, array &$params, &$callingObject)
    {
        return GeneralUtility::callUserFunction($funcName, $params, $callingObject);
    }

    public function getFileAbsFileName(string $filename): string
    {
        return GeneralUtility::getFileAbsFileName($filename);
    }

    public function getIndpEnv(string $getEnvName)
    {
        return GeneralUtility::getIndpEnv($getEnvName);
    }

    public function getRandomHexString(int $count)
    {
        if (class_exists('TYPO3\\CMS\\Core\\Crypto\\Random')) {
            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection We can not import it if it does not exist. */
            $random = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Crypto\Random::class);
            return $random->generateRandomHexString($count);
        } else {
            /** @noinspection PhpDeprecationInspection We already use the newer method if it exists. */
            return GeneralUtility::getRandomHexString($count);
        }
    }
}
