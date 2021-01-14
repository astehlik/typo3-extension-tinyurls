<?php
declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional\Domain\Repository;

use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Object\ImplementationManager;
use Tx\Tinyurls\Tests\Functional\AbstractFunctionalTestCase;

class TinyUrlRepositoryTest extends AbstractFunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/tinyurls'];

    /**
     * @var TinyUrlRepository
     */
    private $tinyUrlRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tinyUrlRepository = ImplementationManager::getInstance()->getTinyUrlRepository();
    }

    /**
     * @test
     */
    public function countTinyUrlHitRaisesCounter()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/Database/tinyurl.xml');

        $tinyUrl = $this->tinyUrlRepository->findTinyUrlByKey('9499fjf');
        $this->assertEquals(0, $tinyUrl->getCounter());

        $tinyUrl = $this->tinyUrlRepository->countTinyUrlHit($tinyUrl);
        $tinyUrl = $this->tinyUrlRepository->countTinyUrlHit($tinyUrl);

        $this->assertEquals(2, $tinyUrl->getCounter());
    }
}
