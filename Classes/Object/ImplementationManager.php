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
    protected $tinyUrlRepositoryClass;

    protected $urlKeyGeneratorClass;

    public function __construct()
    {
        if (class_exists('TYPO3\\CMS\\Core\\Database\\Query\\QueryBuilder')) {
            $this->tinyUrlRepositoryClass = TinyUrlDoctrineRepository::class;
        } else {
            $this->tinyUrlRepositoryClass = TinyUrlDatabaseRepository::class;
        }

        $this->urlKeyGeneratorClass = Base62UrlKeyGenerator::class;
    }

    public static function getInstance(): ImplementationManager
    {
        return GeneralUtility::makeInstance(ImplementationManager::class);
    }

    public function getTinyUrlRepository(): TinyUrlRepository
    {
        return GeneralUtility::makeInstance($this->tinyUrlRepositoryClass);
    }

    public function getUrlGeyGenerator(): UrlKeyGenerator
    {
        return GeneralUtility::makeInstance($this->urlKeyGeneratorClass);
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
