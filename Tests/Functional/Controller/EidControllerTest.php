<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional\Controller;

use Tx\Tinyurls\Configuration\ExtensionConfiguration;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Tests\Functional\AbstractFunctionalTestCase;
use Tx\Tinyurls\TinyUrl\TinyUrlGenerator;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class EidControllerTest extends AbstractFunctionalTestCase
{
    public function testEidControllerRedirectsToExistingUrl(): void
    {
        $targetUrl = 'https://www.example.com';

        $tinyUrl = TinyUrl::createForUrl($targetUrl);
        $tinyUrl->setCustomUrlKey('b-1234567');
        $this->getTinyUrlGenerator()->generateTinyUrl($tinyUrl);

        $tinyUrl = $this->getTinyUrlRepository()->findTinyUrlByTargetUrl($targetUrl);
        self::assertSame(0, $tinyUrl->getPid());

        $request = (new InternalRequest())->withQueryParameter('eID', 'tx_tinyurls')
            ->withQueryParameter('tx_tinyurls[key]', 'b-1234567');

        $response = $this->executeFrontendSubRequest($request);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://www.example.com', $response->getHeaderLine('Location'));
    }

    public function testEidControllerUsesSiteConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/pages.csv');
        $this->setUpFrontendSite(1);
        $site = $this->getSiteByPageId(1);

        $targetUrl = 'https://www.example.com';

        $tinyUrl = TinyUrl::createForUrl($targetUrl);
        $tinyUrl->setCustomUrlKey('b-1234567');
        $this->getTinyUrlGenerator()->generateTinyUrlForSite($tinyUrl, $site);

        $this->getExtensionConfiguration()->setSite($this->getSiteByPageId(1));
        $tinyUrl = $this->getTinyUrlRepository()->findTinyUrlByTargetUrl($targetUrl);
        $this->getExtensionConfiguration()->reset();

        self::assertSame(1, $tinyUrl->getPid());

        $request = (new InternalRequest())
            ->withQueryParameter('eID', 'tx_tinyurls')
            ->withQueryParameter('tx_tinyurls[key]', 'b-1234567');

        $response = $this->executeFrontendSubRequest($request);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('https://www.example.com', $response->getHeaderLine('Location'));
    }

    private function getExtensionConfiguration(): ExtensionConfiguration
    {
        return $this->get(ExtensionConfiguration::class);
    }

    private function getSiteByPageId(int $pageId): Site
    {
        return $this->getSiteFinder()->getSiteByPageId($pageId);
    }

    private function getSiteFinder(): SiteFinder
    {
        return $this->get(SiteFinder::class);
    }

    private function getTinyUrlGenerator(): TinyUrlGenerator
    {
        return $this->get(TinyUrlGenerator::class);
    }

    private function getTinyUrlRepository(): TinyUrlRepository
    {
        return $this->get(TinyUrlRepository::class);
    }
}
