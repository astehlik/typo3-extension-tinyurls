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
use Tx\Tinyurls\Object\ImplementationManager;
use Tx\Tinyurls\Utils\UrlUtils;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class is responsible for generating tiny Urls based on configuration
 * options and extension configuration
 */
class TinyUrlGenerator
{
    /**
     * @var string
     */
    protected $comment = '';

    /**
     * If this option is 1 the URL will be deleted from the database
     * on the first hit
     *
     * @var bool
     */
    protected $optionDeleteOnUse = false;

    /**
     * With this option the user can specify a custom URL key
     *
     * @var string|bool
     */
    protected $optionUrlKey = false;

    /**
     * If this value is set to a timestamp the URL will be invalid
     * after this timestamp has passed.
     *
     * @var int
     */
    protected $optionValidUntil = 0;

    /**
     * @var TinyUrlRepository
     */
    protected $tinyUrlRepository;

    /**
     * @var UrlUtils
     */
    protected $urlUtils;

    public function injectTinyUrlRepository(TinyUrlRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    public function injectUrlUtils(UrlUtils $urlUtils)
    {
        $this->urlUtils = $urlUtils;
    }

    /**
     * Builds a complete tiny URL based on the given URL key and the createSpeakingURLs setting.
     *
     * @param string $tinyUrlKey
     * @return string
     * @deprecated Use UrlTils::buildTinyUrl() instead.
     */
    public function buildTinyUrl(string $tinyUrlKey): string
    {
        return $this->getUrlUtils()->buildTinyUrl($tinyUrlKey);
    }

    /**
     * This method generates a tiny URL, stores it in the database
     * and returns the full URL
     *
     * @param string $targetUrl The URL that should be minified
     * @return string The generated tinyurl
     */
    public function getTinyUrl(string $targetUrl): string
    {
        if (empty($targetUrl)) {
            return $targetUrl;
        }

        try {
            $tinyUrl = $this->getTinyUrlRepository()->findTinyUrlByTargetUrl($targetUrl);
        } catch (TinyUrlNotFoundException $e) {
            $tinyUrl = $this->generateNewTinyurl($targetUrl);
        }

        $tinyUrl = $this->getUrlUtils()->buildTinyUrl($tinyUrl->getUrlkey());

        return $tinyUrl;
    }

    /**
     * Sets the comment for the next tinyurl that is generated.
     *
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = (string)$comment;
    }

    /**
     * Sets the deleteOnUse option, if 1 the URL will be deleted from
     * the database on the first hit
     *
     * @param bool $deleteOnUse
     */
    public function setOptionDeleteOnUse($deleteOnUse)
    {
        $this->optionDeleteOnUse = (bool)$deleteOnUse;
    }

    /**
     * Sets a custom URL key, must be unique
     *
     * @param string $urlKey
     */
    public function setOptionUrlKey($urlKey)
    {
        if (!empty($urlKey)) {
            $this->optionUrlKey = $urlKey;
        } else {
            $this->optionUrlKey = false;
        }
    }

    /**
     * Sets the timestamp until the generated URL is valid
     *
     * @param int $validUntil
     */
    public function setOptionValidUntil($validUntil)
    {
        $this->optionValidUntil = intval($validUntil);
    }

    /**
     * Inserts a new record in the database
     *
     * Does not check, if the url hash already exists! This is done in
     * getTinyUrl().
     *
     * @param string $targetUrl
     * @return TinyUrl
     */
    protected function generateNewTinyurl(string $targetUrl): TinyUrl
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl($targetUrl);
        $tinyUrl->setComment($this->comment);

        if ($this->optionDeleteOnUse) {
            $tinyUrl->enableDeleteOnUse();
        }

        if ($this->optionValidUntil > 0) {
            $tinyUrl->setValidUntil(new \DateTime('@' . $this->optionValidUntil));
        }

        if ($this->optionUrlKey !== false) {
            $tinyUrl->setCustomUrlKey($this->optionUrlKey);
        }

        $this->getTinyUrlRepository()->insertNewTinyUrl($tinyUrl);

        return $tinyUrl;
    }

    /**
     * @return TinyUrlRepository
     * @codeCoverageIgnore
     */
    protected function getTinyUrlRepository(): TinyUrlRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = ImplementationManager::getInstance()->getTinyUrlRepository();
        }
        return $this->tinyUrlRepository;
    }

    /**
     * @return UrlUtils
     * @codeCoverageIgnore
     */
    protected function getUrlUtils(): UrlUtils
    {
        if ($this->urlUtils === null) {
            $this->urlUtils = GeneralUtility::makeInstance(UrlUtils::class);
        }
        return $this->urlUtils;
    }
}
