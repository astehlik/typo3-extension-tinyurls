<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Unit\Object;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Object\ImplementationManager;

class ImplementationManagerTest extends TestCase
{
    /**
     * @var ImplementationManager
     */
    protected $implementationManager;

    protected function setUp()
    {
        $this->implementationManager = new ImplementationManager();
    }

    public function testSetTinyUrlRepositoryClassSetsClassName()
    {
        $this->implementationManager->setTinyUrlRepositoryClass('new class');
        $this->assertEquals('new class', $this->implementationManager->getTinyUrlRepositoryClass());
    }

    public function testSetUrlKeyGeneratorClassSetsClassName()
    {
        $this->implementationManager->setUrlKeyGeneratorClass('new gen class');
        $this->assertEquals('new gen class', $this->implementationManager->getUrlKeyGeneratorClass());
    }

    public function testTinyUrlRepositoryClassDefaultsToDoctrineRepositoryIfExists()
    {
        if (class_exists('TYPO3\\CMS\\Core\\Database\\Query\\QueryBuilder')) {
            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $this->assertEquals(
                \Tx\Tinyurls\Domain\Repository\TinyUrlDoctrineRepository::class,
                $this->implementationManager->getTinyUrlRepositoryClass()
            );
        } else {
            /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
            $this->assertEquals(
                \Tx\Tinyurls\Domain\Repository\TinyUrlDatabaseRepository::class,
                $this->implementationManager->getTinyUrlRepositoryClass()
            );
        }
    }
}
