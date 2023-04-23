<?php

declare(strict_types=1);

use PhpCsFixer\Config;

if (PHP_SAPI !== 'cli') {
    exit('This script supports command line usage only. Please check your command.');
}

$rules = include __DIR__ . '/../../.Build/vendor/de-swebhosting/php-codestyle/PhpCsFixer/Psr12DefaultRules.php';

return (new Config())
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->ignoreVCSIgnored(true)
            ->in(realpath(__DIR__ . '/../../'))
            ->exclude(['.Build'])
            ->notName(['ext_emconf.php', 'ext_localconf.php', 'ext_tables.php'])
    )
    ->setRiskyAllowed(true)
    ->setRules($rules);
