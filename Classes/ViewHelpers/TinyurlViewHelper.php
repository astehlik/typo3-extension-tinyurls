<?php

declare(strict_types=1);

namespace Tx\Tinyurls\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Closure;
use Tx\Tinyurls\TinyUrl\Api as TinyUrlApi;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A view helper for shortening URLs.
 *
 * = Examples =
 *
 * <code title="Shortened URL">
 * <mynamespace:tinyurl url="http://www.google.de" onlyOneTimeValid="0" validUntil="1484740235"
 *                      urlKey="TvRydDxwK8JOreSQ0zlCVZmtkLMfFn1G7HEhN9Bo546cjbgispUaqA3IW2PYuX" />
 * </code>
 * <output>
 * http://mytypo3page.tld/index.php?eID=tx_tinyurls&tx_tinyurls[key]=Aefc-3E
 * (depending on the extension manager settings of tinyurl and viewhelper parameters)
 * </output>
 */
class TinyurlViewHelper extends AbstractViewHelper
{
    /**
     * @var TinyUrlApi
     */
    private static $tinyUrlApi;

    public function initializeArguments()
    {
        $this->registerArgument('url', 'string', 'The Url to be shortened', false, null);
        $this->registerArgument(
            'onlyOneTimeValid',
            'boolean',
            'If this is is true, the tiny URL is deleted from the database on the first hit.',
            false,
            false
        );
        $this->registerArgument('validUntil', 'int', 'Timestamp until generated link is valid', false, 0);
        $this->registerArgument('urlKey', 'string', 'Custom url key', false, '');
    }

    /**
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string Rendered link
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $url = $arguments['url'];
        $onlyOneTimeValid = $arguments['onlyOneTimeValid'];
        $validUntil = $arguments['validUntil'];
        $urlKey = $arguments['urlKey'];

        if ($url === null) {
            $url = $renderChildrenClosure();
        }

        $tinyUrlApi = static::getTinyUrlApi();

        if ($onlyOneTimeValid) {
            $tinyUrlApi->setDeleteOnUse($onlyOneTimeValid);
        }

        if ($validUntil > 0) {
            $tinyUrlApi->setValidUntil($validUntil);
        }

        if ($urlKey !== '') {
            $tinyUrlApi->setUrlKey($urlKey);
        }

        $tinyUrl = $tinyUrlApi->getTinyUrl($url);

        return $tinyUrl;
    }

    /**
     * @param TinyUrlApi $tinyUrlApi
     * @internal No public API! Currently used for unit testing.
     */
    public static function setTinyUrlApi(TinyUrlApi $tinyUrlApi)
    {
        static::$tinyUrlApi = $tinyUrlApi;
    }

    /**
     * @return TinyUrlApi
     * @codeCoverageIgnore
     */
    protected static function getTinyUrlApi(): TinyUrlApi
    {
        if (static::$tinyUrlApi) {
            return static::$tinyUrlApi;
        }

        return GeneralUtility::makeInstance(TinyUrlApi::class);
    }
}
