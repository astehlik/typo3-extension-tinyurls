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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Use this class for generating tiny URLs in your own extension.
 *
 * @api
 */
class Api
{
    /**
     * @var TinyUrlGenerator
     */
    protected $tinyUrlGenerator;

    /**
     * @var TypoScriptConfigurator
     */
    protected $typoScriptConfigurator;

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
        return $this->getTinyUrlGenerator()->getTinyUrl($targetUrl);
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
        $this->getTypoScriptConfigurator()->initializeConfigFromTyposcript($config, $contentObject);
    }

    /**
     * Sets the comment for the created tiny URL.
     */
    public function setComment(string $comment): void
    {
        $this->getTinyUrlGenerator()->setComment($comment);
    }

    /**
     * Sets the deleteOnUse option, if TRUE the URL will be deleted from
     * the database on the first hit.
     */
    public function setDeleteOnUse(bool $deleteOnUse): void
    {
        $this->getTinyUrlGenerator()->setOptionDeleteOnUse($deleteOnUse);
    }

    public function setTinyUrlGenerator(TinyUrlGenerator $tinyUrlGenerator): void
    {
        $this->tinyUrlGenerator = $tinyUrlGenerator;
    }

    public function setTypoScriptConfigurator(TypoScriptConfigurator $typoScriptConfigurator): void
    {
        $this->typoScriptConfigurator = $typoScriptConfigurator;
    }

    /**
     * Sets a custom URL key, must be unique.
     */
    public function setUrlKey(string $urlKey): void
    {
        $this->getTinyUrlGenerator()->setOptionUrlKey($urlKey);
    }

    /**
     * Sets the timestamp until the generated URL is valid.
     */
    public function setValidUntil(int $validUntil): void
    {
        $this->getTinyUrlGenerator()->setOptionValidUntil($validUntil);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getTinyUrlGenerator(): TinyUrlGenerator
    {
        if ($this->tinyUrlGenerator === null) {
            $this->tinyUrlGenerator = GeneralUtility::makeInstance(TinyUrlGenerator::class);
        }
        return $this->tinyUrlGenerator;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getTypoScriptConfigurator(): TypoScriptConfigurator
    {
        if ($this->typoScriptConfigurator === null) {
            $this->typoScriptConfigurator = GeneralUtility::makeInstance(
                TypoScriptConfigurator::class,
                $this->getTinyUrlGenerator()
            );
        }
        return $this->typoScriptConfigurator;
    }
}
