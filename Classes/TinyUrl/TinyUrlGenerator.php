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

use Tx\Tinyurls\Configuration\TypoScriptConfigurator;
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
    private readonly TinyUrl $tinyurl;

    public function __construct(
        private readonly TinyUrlRepository $tinyUrlRepository,
        private readonly TypoScriptConfigurator $typoScriptConfigurator,
        private readonly UrlUtils $urlUtils,
    ) {
        $this->tinyurl = TinyUrl::createNew();
    }

    public function generateTinyUrl(TinyUrl $tinyUrl): string
    {
        if ($tinyUrl->getTargetUrl() === '') {
            return '';
        }

        $tinyUrl = $this->createOrFetchTinyUrl($tinyUrl);

        return $this->urlUtils->buildTinyUrl($tinyUrl->getUrlkey());
    }

    /**
     * This method generates a tiny URL, stores it in the database
     * and returns the full URL.
     *
     * @param string $targetUrl The URL that should be minified
     *
     * @return string The generated tinyurl
     *
     * @deprecated will be removed with the next major version! Use generateTinyUrl() instead
     */
    public function getTinyUrl(string $targetUrl): string
    {
        if (empty($targetUrl)) {
            return $targetUrl;
        }

        try {
            $tinyUrl = $this->tinyUrlRepository->findTinyUrlByTargetUrl($targetUrl);
        } catch (TinyUrlNotFoundException) {
            $tinyUrl = clone $this->tinyurl;
            $tinyUrl->setTargetUrl($targetUrl);
            $this->tinyUrlRepository->insertNewTinyUrl($tinyUrl);
        }

        return $this->urlUtils->buildTinyUrl($tinyUrl->getUrlkey());
    }

    /**
     * Sets the comment for the next tinyurl that is generated.
     *
     * @deprecated Will be removed in next major version. Use TinyUrl model instead.
     */
    public function setComment(string $comment): void
    {
        $this->tinyurl->setComment($comment);
    }

    /**
     * Sets the deleteOnUse option, if 1 the URL will be deleted from
     * the database on the first hit.
     *
     * @deprecated Will be removed in next major version. Use TinyUrl model instead.
     */
    public function setOptionDeleteOnUse(bool $deleteOnUse): void
    {
        $this->typoScriptConfigurator->setOptionDeleteOnUse($this->tinyurl, $deleteOnUse);
    }

    /**
     * Sets a custom URL key, must be unique.
     *
     * @deprecated Will be removed in next major version. Use TinyUrl model instead.
     */
    public function setOptionUrlKey(string $urlKey): void
    {
        $this->typoScriptConfigurator->setOptionUrlKey($this->tinyurl, $urlKey);
    }

    /**
     * Sets the timestamp until the generated URL is valid.
     *
     * @deprecated Will be removed in next major version. Use TinyUrl model instead.
     */
    public function setOptionValidUntil(int $validUntil): void
    {
        $this->typoScriptConfigurator->setOptionValidUntil($this->tinyurl, $validUntil);
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
