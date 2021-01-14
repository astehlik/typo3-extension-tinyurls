<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Hooks\DatabaseRecordList;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use Tx\Tinyurls\Utils\UrlUtils;

return function (ContainerConfigurator $configurator) {
    $configurator->services()
        ->defaults()->autowire()
        ->set(ExtensionConfiguration::class, ExtensionConfiguration::class)
        ->set(DatabaseRecordList::class, DatabaseRecordList::class)
        ->set(GeneralUtilityWrapper::class, GeneralUtilityWrapper::class)
        ->set(UrlUtils::class, UrlUtils::class);
};
