<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional;

use Doctrine\DBAL\ParameterType;
use Symfony\Component\Yaml\Yaml;
use Tx\Tinyurls\Configuration\ConfigKeys;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractFunctionalTestCase extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = ['EXTENSIONS' => ['tinyurls' => [ConfigKeys::BASE_URL => 'http://localhost/']]];

    protected array $testExtensionsToLoad = ['typo3conf/ext/tinyurls'];

    protected function getTinyUrlRow(): array
    {
        $builder = $this->getConnectionPool()->getQueryBuilderForTable('tx_tinyurls_urls');
        $builder->select('*')
            ->from('tx_tinyurls_urls')
            ->where($builder->expr()->eq('uid', $builder->createNamedParameter(1, ParameterType::INTEGER)));
        return $builder->executeQuery()->fetchAssociative();
    }

    /**
     * Create a simple site config for the tests that
     * call a frontend page.
     */
    protected function setUpFrontendSite(
        int $pageId,
        array $additionalLanguages = [],
        array $additionalConfiguration = [],
    ): void {
        $languages = [
            [
                'title' => 'English',
                'enabled' => true,
                'languageId' => 0,
                'base' => '/',
                'typo3Language' => 'default',
                'locale' => 'en_US.UTF-8',
                'iso-639-1' => 'en',
                'navigationTitle' => '',
                'hreflang' => '',
                'direction' => '',
                'flag' => 'us',
            ],
            [
                'title' => 'German',
                'enabled' => true,
                'languageId' => 1,
                'base' => '/de/',
                'typo3Language' => 'de',
                'locale' => 'de_DE.UTF-8',
                'iso-639-1' => 'de',
                'navigationTitle' => '',
                'hreflang' => '',
                'direction' => '',
                'flag' => 'de',
            ],
        ];
        $languages = array_merge($languages, $additionalLanguages);
        $configuration = [
            'rootPageId' => $pageId,
            'base' => 'http://localhost/',
            'languages' => $languages,
            'errorHandling' => [],
            'routes' => [],
            'tinyurls' => [ConfigKeys::URL_RECORD_STORAGE_PID => 1],
        ];
        $configuration = array_merge_recursive($configuration, $additionalConfiguration);
        GeneralUtility::mkdir_deep($this->instancePath . '/typo3conf/sites/testing/');
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        $fileName = $this->instancePath . '/typo3conf/sites/testing/config.yaml';
        GeneralUtility::writeFile($fileName, $yamlFileContents);
        // Ensure that no other site configuration was cached before
        $cache = $this->getCacheManager()->getCache('core');
        if ($cache->has('site-configuration')) {
            $cache->remove('site-configuration');
        }
    }

    private function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
