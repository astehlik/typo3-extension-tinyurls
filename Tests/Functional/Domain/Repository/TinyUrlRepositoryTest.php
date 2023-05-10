<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional\Domain\Repository;

use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Tests\Functional\AbstractFunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TinyUrlRepositoryTest extends AbstractFunctionalTestCase
{
    private TinyUrlRepository $tinyUrlRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tinyUrlRepository = GeneralUtility::makeInstance(TinyUrlRepository::class);
    }

    public function testCountTinyUrlHitRaisesCounter(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/tinyurl.csv');

        $tinyUrl = $this->tinyUrlRepository->findTinyUrlByKey('9499fjf');
        self::assertSame(0, $tinyUrl->getCounter());

        $tinyUrl = $this->tinyUrlRepository->countTinyUrlHit($tinyUrl);
        $tinyUrl = $this->tinyUrlRepository->countTinyUrlHit($tinyUrl);

        self::assertSame(2, $tinyUrl->getCounter());
    }
}
