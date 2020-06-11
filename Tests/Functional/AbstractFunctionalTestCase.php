<?php
declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional;

use Doctrine\DBAL\FetchMode;
use PDO;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractFunctionalTestCase extends FunctionalTestCase
{
    protected function getTinyUrlRow(): array
    {
        $builder = $this->getConnectionPool()->getQueryBuilderForTable('tx_tinyurls_urls');
        $builder->select('*')
            ->from('tx_tinyurls_urls')
            ->where($builder->expr()->eq('uid', $builder->createNamedParameter(1, PDO::PARAM_INT)));
        return $builder->execute()->fetch(FetchMode::ASSOCIATIVE);
    }
}
