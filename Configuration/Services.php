<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Configuration\TypoScriptConfigurator;
use Tx\Tinyurls\Controller\EidController;
use Tx\Tinyurls\Domain\Repository\TinyUrlDoctrineRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Domain\Validator\TinyUrlValidator;
use Tx\Tinyurls\FormEngine\TinyUrlDisplay;
use Tx\Tinyurls\Hooks\DatabaseRecordList;
use Tx\Tinyurls\Hooks\TceDataMap;
use Tx\Tinyurls\Hooks\TypoLink;
use Tx\Tinyurls\TinyUrl\Api;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface;
use Tx\Tinyurls\UrlKeyGenerator\Base62UrlKeyGenerator;
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;
use Tx\Tinyurls\Utils\GeneralUtilityWrapper;
use Tx\Tinyurls\Utils\UrlUtils;
use Tx\Tinyurls\Utils\UrlUtilsInterface;
use Tx\Tinyurls\ViewHelpers\TinyurlViewHelper;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;

return static function (ContainerConfigurator $configurator): void {
    $configurator->services()
        ->defaults()->autowire()->autoconfigure()->public()

        // Configure implementations of interfaces.
        ->set(UrlKeyGenerator::class, Base62UrlKeyGenerator::class)
        ->set(TinyUrlRepository::class, TinyUrlDoctrineRepository::class)
        ->set(TinyUrlGeneratorInterface::class, TinyUrlGenerator::class)
        ->set(UrlUtilsInterface::class, UrlUtils::class)

        // Configure services.
        ->set(ExtensionConfiguration::class, ExtensionConfiguration::class)
        ->set(TypoScriptConfigurator::class, TypoScriptConfigurator::class)
        ->set(EidController::class, EidController::class)
        ->set(TinyUrlValidator::class, TinyUrlValidator::class)
        ->set(TinyUrlDisplay::class, TinyUrlDisplay::class)
        ->set(TceDataMap::class, TceDataMap::class)
        ->set(Api::class, Api::class)
        ->set(TinyUrlGenerator::class, TinyUrlGenerator::class)
        ->set(GeneralUtilityWrapper::class, GeneralUtilityWrapper::class)
        ->set(UrlUtils::class, UrlUtils::class)
        ->set(TinyurlViewHelper::class, TinyurlViewHelper::class)

        // Configure event listeners.
        ->set(TypoLink::class, TypoLink::class)
        ->tag(
            'event.listener',
            [
                'event' => AfterLinkIsGeneratedEvent::class,
                'identifier' => 'tinyurlsAfterLinkIsGeneratedEventListener',
            ],
        )
        ->set(DatabaseRecordList::class, DatabaseRecordList::class)
        ->tag(
            'event.listener',
            [
                'event' => ModifyDatabaseQueryForRecordListingEvent::class,
                'identifier' => 'tinyurlsModifyDatabaseQueryForRecordListingEventListener',
            ],
        );
};
