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

use Tx\Tinyurls\TinyUrl\Api as TinyUrlApi;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

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
    protected $tinyUrlApi;

    /**
     * @param string $url The Url to be shorting
     * @param bool $onlyOneTimeValid If this is is true, the tiny URL is deleted from the database on the first hit.
     * @param int $validUntil Timestamp until generated link is valid
     * @param string $urlKey Custom url key
     * @return string Rendered link
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function render($url = null, $onlyOneTimeValid = false, $validUntil = 0, $urlKey = '')
    {
        if ($url === null) {
            $url = $this->renderChildren();
        }

        $tinyUrlApi = $this->getTinyUrlApi();

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

        $tinyUrl = $this->escapeOutputForLegacyFluid($tinyUrl);

        return $tinyUrl;
    }

    public function setTinyUrlApi(TinyUrlApi $tinyUrlApi)
    {
        $this->tinyUrlApi = $tinyUrlApi;
    }

    /**
     * Backward compatibility for TYPO3 7: we need to do the escaping manually.
     *
     * @param string $output
     * @return string
     * @codeCoverageIgnore
     */
    protected function escapeOutputForLegacyFluid(string $output): string
    {
        // Escaping property exists, no need for additional escaping.
        if (property_exists($this, 'escapeOutput')) {
            return $output;
        } else {
            return htmlspecialchars($output);
        }
    }

    /**
     * @return TinyUrlApi
     * @codeCoverageIgnore
     */
    protected function getTinyUrlApi(): TinyUrlApi
    {
        if ($this->tinyUrlApi === null) {
            $this->tinyUrlApi = GeneralUtility::makeInstance(TinyUrlApi::class);
        }
        return $this->tinyUrlApi;
    }
}
