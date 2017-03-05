<?php
declare(strict_types = 1);
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

use Tx\Tinyurls\Domain\Repository\TinyUrlDatabaseRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlDoctrineRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\UrlKeyGenerator\Base62UrlKeyGenerator;
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImplementationManager implements SingletonInterface
{
    /**
     * @var TinyUrlRepository
     */
    protected $tinyUrlRepository;

    /**
     * @var string
     */
    protected $tinyUrlRepositoryClass;

    /**
     * @var UrlKeyGenerator
     */
    protected $urlKeyGenerator;

    /**
     * @var string
     */
    protected $urlKeyGeneratorClass;

    public function __construct()
    {
        if (class_exists('TYPO3\\CMS\\Core\\Database\\Query\\QueryBuilder')) {
            $this->tinyUrlRepositoryClass = TinyUrlDoctrineRepository::class;
        } else {
            // @codeCoverageIgnoreStart
            $this->tinyUrlRepositoryClass = TinyUrlDatabaseRepository::class;
            // @codeCoverageIgnoreEnd
        }

        $this->urlKeyGeneratorClass = Base62UrlKeyGenerator::class;
    }

    public static function getInstance(): ImplementationManager
    {
        return GeneralUtility::makeInstance(ImplementationManager::class);
    }

    /**
     * @return TinyUrlRepository
     * @codeCoverageIgnore
     */
    public function getTinyUrlRepository(): TinyUrlRepository
    {
        if ($this->tinyUrlRepository === null) {
            $this->tinyUrlRepository = GeneralUtility::makeInstance($this->tinyUrlRepositoryClass);
        }
        return $this->tinyUrlRepository;
    }

    public function getTinyUrlRepositoryClass(): string
    {
        return $this->tinyUrlRepositoryClass;
    }

    public function getUrlKeyGenerator(): UrlKeyGenerator
    {
        if ($this->urlKeyGenerator === null) {
            $this->urlKeyGenerator = GeneralUtility::makeInstance($this->urlKeyGeneratorClass);
        }
        return $this->urlKeyGenerator;
    }

    public function getUrlKeyGeneratorClass(): string
    {
        return $this->urlKeyGeneratorClass;
    }


    public function setTinyUrlRepository(TinyUrlRepository $tinyUrlRepository)
    {
        $this->tinyUrlRepository = $tinyUrlRepository;
    }

    public function setTinyUrlRepositoryClass(string $tinyUrlRepositoryClass)
    {
        $this->tinyUrlRepository = null;
        $this->tinyUrlRepositoryClass = $tinyUrlRepositoryClass;
    }

    public function setUrlKeyGenerator(UrlKeyGenerator $urlKeyGenerator)
    {
        $this->urlKeyGenerator = $urlKeyGenerator;
    }

    public function setUrlKeyGeneratorClass(string $urlKeyGeneratorClass)
    {
        $this->urlKeyGenerator = null;
        $this->urlKeyGeneratorClass = $urlKeyGeneratorClass;
    }
}
