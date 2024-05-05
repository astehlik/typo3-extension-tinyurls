<?php

declare(strict_types=1);

namespace Tx\Tinyurls\TinyUrl;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Utils\UrlUtilsInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * This class is responsible for generating tiny Urls based on configuration
 * options and extension configuration.
 */
readonly class TinyUrlGenerator implements TinyUrlGeneratorInterface
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private TinyUrlRepository $tinyUrlRepository,
        private UrlUtilsInterface $urlUtils,
    ) {}

    public function generateTinyUrl(TinyUrl $tinyUrl): string
    {
        return $this->generateTinyUrlForSite($tinyUrl, null);
    }

    public function generateTinyUrlForSite(TinyUrl $tinyUrl, ?SiteInterface $site): string
    {
        $targetUrl = $tinyUrl->getTargetUrl();

        if ($targetUrl === '') {
            return '';
        }

        $this->extensionConfiguration->setSite($site);

        $tinyUrl = $this->createOrFetchTinyUrl($targetUrl, $tinyUrl);

        $tinyUrl = $this->urlUtils->buildTinyUrl($tinyUrl->getUrlkey());

        $this->extensionConfiguration->reset();

        return $tinyUrl;
    }

    /**
     * @param non-empty-string $targetUrl
     */
    private function createOrFetchTinyUrl(string $targetUrl, TinyUrl $tinyUrl): TinyUrl
    {
        try {
            return $this->tinyUrlRepository->findTinyUrlByTargetUrl($targetUrl);
        } catch (TinyUrlNotFoundException) {
            $this->tinyUrlRepository->insertNewTinyUrl($tinyUrl);
            return $this->tinyUrlRepository->findTinyUrlByTargetUrl($targetUrl);
        }
    }
}
