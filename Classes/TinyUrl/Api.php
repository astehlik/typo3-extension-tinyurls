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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use DateTimeImmutable;

/**
 * Use this class for generating tiny URLs in your own extension.
 *
 * @api
 */
class Api
{
    private TinyUrl $tinyUrl;

    public function __construct(
        private readonly TinyUrlGenerator $tinyUrlGenerator,
        private readonly TypoScriptConfigurator $typoScriptConfigurator,
    ) {
        $this->tinyUrl = TinyUrl::createNew();
    }

    /**
     * Returns the final tiny URL for the given target URL using the
     * configuration options that have been provided by the setters or
     * by TypoScript.
     *
     * @return string the tiny URL
     *
     * @api
     */
    public function getTinyUrl(string $targetUrl): string
    {
        $tinyUrl = clone $this->tinyUrl;
        $tinyUrl->setTargetUrl($targetUrl);
        return $this->tinyUrlGenerator->generateTinyUrl($tinyUrl);
    }

    /**
     * @internal for testing purposes only
     */
    public function getTinyUrlInstance(): TinyUrl
    {
        return $this->tinyUrl;
    }

    /**
     * Initializes the configuration of the tiny URL generator based on the given
     * TypoScript configuration. The content object is used to parse values with
     * stdWrap.
     *
     * @param array $config the TypoScript configuration of a typolink, the config options must be set within
     *                      the tinyurl. namespace
     * @param ContentObjectRenderer $contentObject The parent content object (used for running stdWrap)
     *
     * @api
     */
    public function initializeConfigFromTyposcript(array $config, ContentObjectRenderer $contentObject): void
    {
        $this->typoScriptConfigurator->initializeConfigFromTyposcript($this->tinyUrl, $config, $contentObject);
    }

    public function reset(): void
    {
        $this->tinyUrl = TinyUrl::createNew();
    }

    /**
     * Sets the comment for the created tiny URL.
     */
    public function setComment(string $comment): void
    {
        $this->tinyUrl->setComment($comment);
    }

    /**
     * Sets the deleteOnUse option, if TRUE the URL will be deleted from
     * the database on the first hit.
     */
    public function setDeleteOnUse(bool $deleteOnUse): void
    {
        if (!$deleteOnUse) {
            $this->tinyUrl->disableDeleteOnUse();
            return;
        }

        $this->tinyUrl->enableDeleteOnUse();
    }

    /**
     * Sets a custom URL key, must be unique.
     */
    public function setUrlKey(string $urlKey): void
    {
        $this->tinyUrl->setCustomUrlKey($urlKey);
    }

    /**
     * Sets the timestamp until the generated URL is valid.
     */
    public function setValidUntil(int $validUntil): void
    {
        $this->tinyUrl->setValidUntil(new DateTimeImmutable('@' . $validUntil));
    }
}
