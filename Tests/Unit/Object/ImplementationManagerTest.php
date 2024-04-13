<?php

declare(strict_types=1);

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
use Tx\Tinyurls\Domain\Repository\TinyUrlDoctrineRepository;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Object\ImplementationManager;
use Tx\Tinyurls\UrlKeyGenerator\Base62UrlKeyGenerator;
use Tx\Tinyurls\UrlKeyGenerator\UrlKeyGenerator;

class ImplementationManagerTest extends TestCase
{
    /**
     * @var ImplementationManager
     */
    protected $implementationManager;

    protected function setUp(): void
    {
        $this->implementationManager = new ImplementationManager();
    }

    public function testResetToDefaultsUsesBase62UrlKeyGenerator(): void
    {
        $this->implementationManager->restoreDefaults();
        self::assertSame(Base62UrlKeyGenerator::class, $this->implementationManager->getUrlKeyGeneratorClass());
    }

    public function testResetToDefaultsUsesDoctrineRepositoryIfAvailable(): void
    {
        $this->implementationManager->restoreDefaults();
        self::assertSame(
            TinyUrlDoctrineRepository::class,
            $this->implementationManager->getTinyUrlRepositoryClass(),
        );
    }

    public function testSetTinyUrlRepositoryClassSetsClassName(): void
    {
        $this->implementationManager->setTinyUrlRepositoryClass('new class');
        self::assertSame('new class', $this->implementationManager->getTinyUrlRepositoryClass());
    }

    public function testSetTinyUrlRepositorySetsTinyUrlRepositoryInstance(): void
    {
        /** @var TinyUrlRepository $tinyUrlRepository */
        $tinyUrlRepository = $this->createMock(TinyUrlRepository::class);
        $this->implementationManager->setTinyUrlRepository($tinyUrlRepository);
        self::assertSame($tinyUrlRepository, $this->implementationManager->getTinyUrlRepository());
    }

    public function testSetUrlKeyGeneratorClassSetsClassName(): void
    {
        $this->implementationManager->setUrlKeyGeneratorClass('new gen class');
        self::assertSame('new gen class', $this->implementationManager->getUrlKeyGeneratorClass());
    }

    public function testSetUrlKeyGeneratorSetsUrlKeyGeneratorInstance(): void
    {
        /** @var UrlKeyGenerator $urlKeyGenerator */
        $urlKeyGenerator = $this->createMock(UrlKeyGenerator::class);
        $this->implementationManager->setUrlKeyGenerator($urlKeyGenerator);
        self::assertSame($urlKeyGenerator, $this->implementationManager->getUrlKeyGenerator());
    }
}
