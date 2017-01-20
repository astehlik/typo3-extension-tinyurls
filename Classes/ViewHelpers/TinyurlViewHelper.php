<?php
namespace Tx\Tinyurls\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception;

/**
 * A view helper for shortening URLs.
 *
 * = Examples =
 *
 * <code title="Shortened URL">
 * <mynamespace:tinyurl url="http://www.google.de" onlyOneTimeValid="0" validUntil="1484740235" urlKey="TvRydDxwK8JOreSQ0zlCVZmtkLMfFn1G7HEhN9Bo546cjbgispUaqA3IW2PYuX" />
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
        return static::renderStatic(
            [
                'url' => $url,
                'onlyOneTimeValid' => $onlyOneTimeValid,
                'validUntil' => $validUntil,
                'urlKey' => $urlKey
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return int
     * @throws Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if ($arguments['url'] === null) {
            $arguments['url'] = $renderChildrenClosure();
        }

        $tinyUrlApi = GeneralUtility::makeInstance(\Tx\Tinyurls\TinyUrl\Api::class);

        if ($arguments['onlyOneTimeValid']) {
            $tinyUrlApi->setDeleteOnUse($arguments['onlyOneTimeValid']);
        }

        if ($arguments['validUntil'] > 0) {
            $tinyUrlApi->setValidUntil($arguments['validUntil']);
        }

        if ($arguments['urlKey'] !== '') {
            $tinyUrlApi->setUrlKey($arguments['urlKey']);
        }

        $tinyUrl = $tinyUrlApi->getTinyUrl($arguments['url']);

        return $tinyUrl;
    }
}
