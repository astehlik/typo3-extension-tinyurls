<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Hooks\DatabaseRecordList;
use Tx\Tinyurls\Hooks\TypoLink;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use Tx\Tinyurls\Utils\UrlUtils;
use Tx\Tinyurls\ViewHelpers\TinyurlViewHelper;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;

return function (ContainerConfigurator $configurator): void {
    $configurator->services()
        ->defaults()->autowire()->autoconfigure()
        ->set(ExtensionConfiguration::class, ExtensionConfiguration::class)
        ->set(GeneralUtilityWrapper::class, GeneralUtilityWrapper::class)
        ->set(UrlUtils::class, UrlUtils::class)
        ->set(TinyurlViewHelper::class, TinyurlViewHelper::class)
        ->set(TypoLink::class, TypoLink::class)
        ->tag(
            'event.listener',
            [
                'event' => AfterLinkIsGeneratedEvent::class,
                'identifier' => 'tinyurlsAfterLinkIsGeneratedEventListener',
            ]
        )
        ->set(DatabaseRecordList::class, DatabaseRecordList::class)
        ->tag(
            'event.listener',
            [
                'event' => ModifyDatabaseQueryForRecordListingEvent::class,
                'identifier' => 'tinyurlsModifyDatabaseQueryForRecordListingEventListener',
            ]
        );
};
