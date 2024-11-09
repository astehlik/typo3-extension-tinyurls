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

use DateTimeImmutable;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
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
    public function __construct(private readonly TinyUrlGeneratorInterface $tinyUrlGenerator) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('url', 'string', 'The Url to be shortened');
        $this->registerArgument(
            'onlyOneTimeValid',
            'boolean',
            'If this is is true, the tiny URL is deleted from the database on the first hit.',
            false,
            false,
        );
        $this->registerArgument('validUntil', 'int', 'Timestamp until generated link is valid', false, 0);
        $this->registerArgument('urlKey', 'string', 'Custom url key', false, '');
        $this->registerArgument('site', SiteInterface::class, 'The Site from which the configuration should be loaded');
    }

    /**
     * @return string Rendered link
     */
    public function render(): string
    {
        $url = $this->arguments['url'];
        $onlyOneTimeValid = $this->arguments['onlyOneTimeValid'];
        $validUntil = $this->arguments['validUntil'];
        $urlKey = $this->arguments['urlKey'];
        $site = $this->arguments['site'];

        if ($url === null) {
            $url = $this->renderChildren();
        }

        if (!is_string($url) || $url === '') {
            return '';
        }

        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl($url);

        if ($onlyOneTimeValid === true) {
            $tinyUrl->enableDeleteOnUse();
        }

        if (is_int($validUntil) && $validUntil > 0) {
            $tinyUrl->setValidUntil(new DateTimeImmutable('@' . $validUntil));
        }

        if (is_string($urlKey) && $urlKey !== '') {
            $tinyUrl->setCustomUrlKey($urlKey);
        }

        $site = $site instanceof SiteInterface ? $site : null;

        return $this->tinyUrlGenerator->generateTinyUrlForSite($tinyUrl, $site);
    }
}
