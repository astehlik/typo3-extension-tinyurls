<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Acceptance\Support\Extension;

use Codeception\Event\SuiteEvent;
use De\SWebhosting\Buildtools\Tests\Acceptance\Helper\AcceptanceHelper;
use RuntimeException;
use TYPO3\TestingFramework\Core\Acceptance\Extension\BackendEnvironment;

class BackendTinyurlsEnvironment extends BackendEnvironment
{
    public function __construct(array $config, array $options)
    {
        $this->localConfig = [
            'coreExtensionsToLoad' => AcceptanceHelper::getExtensionsForMinimalUsableSystem(),
            'testExtensionsToLoad' => ['typo3conf/ext/tinyurls'],
            'csvDatabaseFixtures' => [__DIR__ . '/../../Fixtures/BackendEnvironment.csv'],
        ];

        parent::__construct($config, $options);
    }

    public function bootstrapTypo3Environment(SuiteEvent $suiteEvent): void
    {
        parent::bootstrapTypo3Environment($suiteEvent);

        $typo3RootPath = (string)getenv('TYPO3_PATH_ROOT');

        if ($typo3RootPath === '') {
            throw new RuntimeException('TYPO3_PATH_ROOT environment variable is not set');
        }

        $sysextDir = rtrim(ORIGINAL_ROOT, '/') . '/typo3/sysext';
        $rootHtaccess = $sysextDir . '/install/Resources/Private/FolderStructureTemplateFiles/root-htaccess';

        if (!file_exists($rootHtaccess)) {
            throw new RuntimeException('File not found: ' . $rootHtaccess);
        }

        copy($rootHtaccess, $typo3RootPath . '/.htaccess');

        $putenvCode = PHP_EOL
            . 'putenv(\'TYPO3_PATH_ROOT=' . $typo3RootPath . '\');' . PHP_EOL
            . 'putenv(\'TYPO3_PATH_APP=' . $typo3RootPath . '\');' . PHP_EOL
            . PHP_EOL;

        $indexFiles = ['index.php'];

        foreach ($indexFiles as $indexFile) {
            $indexPath = $typo3RootPath . '/' . $indexFile;
            $indexContexts = file_get_contents($indexPath);
            $indexContexts = str_replace('<?php', '<?php ' . $putenvCode, $indexContexts);
            $indexContexts = str_replace(
                'SystemEnvironmentBuilder::run()',
                'SystemEnvironmentBuilder::run(composerMode: false)',
                $indexContexts,
            );
            file_put_contents($indexPath, $indexContexts);
        }
    }
}
