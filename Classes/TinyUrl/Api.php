<?php
declare(strict_types = 1);
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
 * Use this class for generating tiny URLs in your own extension
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
     * Initializes the tinyUrl generator
     */
    public function __construct()
    {
        $this->tinyUrlGenerator = GeneralUtility::makeInstance(TinyUrlGenerator::class);
        $this->typoScriptConfigurator = GeneralUtility::makeInstance(
            TypoScriptConfigurator::class,
            $this->tinyUrlGenerator
        );
    }

    /**
     * Initializes the configuration of the tiny URL generator based on the given
     * TypoScript configuration. The content object is used to parse values with
     * stdWrap
     *
     * @param array $config the TypoScript configuration of a typolink, the config options must be set within
     * the tinyurl. namespace
     * @param ContentObjectRenderer $contentObject The parent content object (used for running stdWrap)
     * @api
     */
    public function initializeConfigFromTyposcript($config, $contentObject)
    {
        $this->typoScriptConfigurator->initializeConfigFromTyposcript($config, $contentObject);
    }

    /**
     * Returns the final tiny URL for the given target URL using the
     * configuration options that have been provided by the setters or
     * by TypoScript
     *
     * @param string $targetUrl
     * @return string the tiny URL
     * @api
     */
    public function getTinyUrl($targetUrl)
    {
        return $this->tinyUrlGenerator->getTinyUrl($targetUrl);
    }

    /**
     * Sets the comment for the created tiny URL.
     *
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->tinyUrlGenerator->setComment($comment);
    }

    /**
     * Sets the deleteOnUse option, if TRUE the URL will be deleted from
     * the database on the first hit
     *
     * @param bool $deleteOnUse
     */
    public function setDeleteOnUse($deleteOnUse)
    {
        $this->tinyUrlGenerator->setOptionDeleteOnUse($deleteOnUse);
    }

    /**
     * Sets a custom URL key, must be unique
     *
     * @param string $urlKey
     */
    public function setUrlKey($urlKey)
    {
        $this->tinyUrlGenerator->setOptionUrlKey($urlKey);
    }

    /**
     * Sets the timestamp until the generated URL is valid
     *
     * @param int $validUntil
     */
    public function setValidUntil($validUntil)
    {
        $this->tinyUrlGenerator->setOptionValidUntil($validUntil);
    }
}
