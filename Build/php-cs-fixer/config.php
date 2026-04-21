<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

if (PHP_SAPI !== 'cli') {
    exit('This script supports command line usage only. Please check your command.');
}

$rules = include __DIR__ . '/../../.Build/vendor/de-swebhosting/php-codestyle/PhpCsFixer/PerCsDefaultRules.php';

return (new Config())
    ->setCacheFile(__DIR__ . '/../../.cache/php-cs-fixer.cache')
    ->setFinder(
        (new Finder())
            ->ignoreVCSIgnored(true)
            ->in(realpath(__DIR__ . '/../../'))
            ->exclude(['.cache', '.Build', 'typo3temp', 'var'])
            ->notName(['ext_emconf.php', 'ext_localconf.php', 'ext_tables.php']),
    )
    ->setRiskyAllowed(true)
    ->setRules($rules);
