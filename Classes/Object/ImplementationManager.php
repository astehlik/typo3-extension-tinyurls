<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Object;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Domain\Repository\TinyUrlDoctrineRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\UrlKeyGenerator\Base62UrlKeyGenerator;
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @deprecated use Service configuration instead
 */
class ImplementationManager implements SingletonInterface
{
    protected ?TinyUrlRepository $tinyUrlRepository;

    protected string $tinyUrlRepositoryClass;

    protected ?UrlKeyGenerator $urlKeyGenerator;

    protected string $urlKeyGeneratorClass;

    public function __construct()
    {
        $this->restoreDefaults();
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public static function getInstance(): self
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function getTinyUrlRepository(): TinyUrlRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = GeneralUtility::makeInstance($this->tinyUrlRepositoryClass);
        }
        return $this->tinyUrlRepository;
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function getTinyUrlRepositoryClass(): string
    {
        return $this->tinyUrlRepositoryClass;
    }

    /**
     * @codeCoverageIgnore
     *
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function getUrlKeyGenerator(): UrlKeyGenerator
    {
        if ($this->urlKeyGenerator === null) {
            $this->urlKeyGenerator = GeneralUtility::makeInstance($this->urlKeyGeneratorClass);
        }
        return $this->urlKeyGenerator;
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function getUrlKeyGeneratorClass(): string
    {
        return $this->urlKeyGeneratorClass;
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function restoreDefaults(): void
    {
        $this->urlKeyGenerator = null;
        $this->tinyUrlRepository = null;

        $this->tinyUrlRepositoryClass = TinyUrlDoctrineRepository::class;
        $this->urlKeyGeneratorClass = Base62UrlKeyGenerator::class;
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function setTinyUrlRepository(TinyUrlRepository $tinyUrlRepository): void
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function setTinyUrlRepositoryClass(string $tinyUrlRepositoryClass): void
    {
        $this->tinyUrlRepository = null;
        $this->tinyUrlRepositoryClass = $tinyUrlRepositoryClass;
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function setUrlKeyGenerator(UrlKeyGenerator $urlKeyGenerator): void
    {
        $this->urlKeyGenerator = $urlKeyGenerator;
    }

    /**
     * @deprecated will be removed in next major version, use Dependency Injection instead
     */
    public function setUrlKeyGeneratorClass(string $urlKeyGeneratorClass): void
    {
        $this->urlKeyGenerator = null;
        $this->urlKeyGeneratorClass = $urlKeyGeneratorClass;
    }
}
