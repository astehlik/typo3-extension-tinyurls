<?php
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

use Tx\Tinyurls\TinyUrl\Api as TinyUrlApi;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

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
class TinyurlViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * @param string $url The Url to be shorting
     * @param bool $onlyOneTimeValid If this is is true, the tiny URL is deleted from the database on the first hit.
     * @param int $validUntil Timestamp until generated link is valid
     * @param string $urlKey Custom url key
     * @return string Rendered link
     */
    public function render($url = null, $onlyOneTimeValid = false, $validUntil = 0, $urlKey = '')
    {
        if ($url === null) {
            $url = $this->renderChildren();
        }

        $tinyUrlApi = GeneralUtility::makeInstance(TinyUrlApi::class);

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
}
