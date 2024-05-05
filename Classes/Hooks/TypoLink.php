<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Hooks;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Configuration\TypoScriptConfigurator;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Frontend\Event\AfterLinkIsGeneratedEvent;

/**
 * Contains a hook for the typolink generation to convert a typolink
 * in a tinyurl. Additionally, it contains a public api for generating
 * a tinyurl in another extension.
 */
readonly class TypoLink
{
    public function __construct(
        private TinyUrlGeneratorInterface $tinyUrlGenerator,
        private TypoScriptConfigurator $typoScriptConfigurator,
    ) {}

    public function __invoke(AfterLinkIsGeneratedEvent $afterLinkIsGeneratedEvent): void
    {
        $linkResult = $afterLinkIsGeneratedEvent->getLinkResult();
        $contentObject = $afterLinkIsGeneratedEvent->getContentObjectRenderer();
        $config = $afterLinkIsGeneratedEvent->getLinkInstructions();

        if ($linkResult->getType() === LinkService::TYPE_EMAIL) {
            return;
        }

        if (!(array_key_exists('tinyurl', $config) && $config['tinyurl'])) {
            return;
        }

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl($linkResult->getUrl());

        $this->typoScriptConfigurator->initializeConfigFromTyposcript($tinyUrl, $config, $contentObject);

        $tinyUrl = $this->tinyUrlGenerator->generateTinyUrlForSite(
            $tinyUrl,
            $contentObject->getRequest()->getAttribute('site'),
        );

        $newResult = $linkResult->withAttributes(['href' => $tinyUrl]);
        $afterLinkIsGeneratedEvent->setLinkResult($newResult);
    }
}
