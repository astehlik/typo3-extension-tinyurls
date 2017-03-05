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
     * @var string
     */
    protected $tinyUrlRepositoryClass;

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
        return GeneralUtility::makeInstance($this->tinyUrlRepositoryClass);
    }

    public function getTinyUrlRepositoryClass(): string
    {
        return $this->tinyUrlRepositoryClass;
    }

    public function getUrlKeyGenerator(): UrlKeyGenerator
    {
        return GeneralUtility::makeInstance($this->urlKeyGeneratorClass);
    }

    public function getUrlKeyGeneratorClass(): string
    {
        return $this->urlKeyGeneratorClass;
    }

    public function setTinyUrlRepositoryClass(string $tinyUrlRepositoryClass)
    {
        $this->tinyUrlRepositoryClass = $tinyUrlRepositoryClass;
    }

    public function setUrlKeyGeneratorClass(string $urlKeyGeneratorClass)
    {
        $this->urlKeyGeneratorClass = $urlKeyGeneratorClass;
    }
}
