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

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use Tx\Tinyurls\Utils\UrlUtils;
use InvalidArgumentException;

/**
 * This class is responsible for generating tiny Urls based on configuration
 * options and extension configuration.
 */
class TinyUrlGenerator
{
    public function __construct(
        private readonly TinyUrlRepository $tinyUrlRepository,
        private readonly UrlUtils $urlUtils,
    ) {
    }

    public function generateTinyUrl(TinyUrl $tinyUrl): string
    {
        if ($tinyUrl->getTargetUrl() === '') {
            return '';
        }

        $tinyUrl = $this->createOrFetchTinyUrl($tinyUrl);

        return $this->urlUtils->buildTinyUrl($tinyUrl->getUrlkey());
    }

    private function createOrFetchTinyUrl(TinyUrl $tinyUrl): TinyUrl
    {
        if ($tinyUrl->getTargetUrl() === '') {
            throw new InvalidArgumentException('Target URL must not be empty!');
        }

        try {
            return $this->tinyUrlRepository->findTinyUrlByTargetUrl($tinyUrl->getTargetUrl());
        } catch (TinyUrlNotFoundException) {
            $this->tinyUrlRepository->insertNewTinyUrl($tinyUrl);
            return $this->tinyUrlRepository->findTinyUrlByTargetUrl($tinyUrl->getTargetUrl());
        }
    }
}
