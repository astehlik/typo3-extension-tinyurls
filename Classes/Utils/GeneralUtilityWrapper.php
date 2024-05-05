<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Closure;

/**
 * A wrapper class for GeneralUtility calls. This allows us better mocking in the Unit tests.
 *
 * @codeCoverageIgnore
 */
class GeneralUtilityWrapper
{
    public function callUserFunction(Closure|string $funcName, mixed &$params, ?object $ref = null): mixed
    {
        return GeneralUtility::callUserFunction($funcName, $params, $ref);
    }

    public function getFileAbsFileName(string $fileName): string
    {
        return GeneralUtility::getFileAbsFileName($fileName);
    }

    public function getIndpEnv(string $getEnvName): null|array|bool|string
    {
        return GeneralUtility::getIndpEnv($getEnvName);
    }

    public function getRandomHexString(int $length): string
    {
        $random = GeneralUtility::makeInstance(Random::class);
        return $random->generateRandomHexString($length);
    }
}
