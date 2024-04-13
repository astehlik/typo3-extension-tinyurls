<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Functional\TinyUrl;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Tests\Functional\AbstractFunctionalTestCase;
use Tx\Tinyurls\TinyUrl\Api;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Functional tests for the tinyurls API.
 */
class ApiTest extends AbstractFunctionalTestCase
{
    private Api $tinyUrlsApi;

    /**
     * Initializes the test subject.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tinyUrlsApi = $this->getContainer()->get(Api::class);
    }

    public function testApiDoesNotSetDeleteOnUseByDefault(): void
    {
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertEmpty($tinyUrlRow['delete_on_use']);
    }

    public function testApiDoesNotSetValidationDateByDefault(): void
    {
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertEmpty($tinyUrlRow['valid_until']);
    }

    public function testApiRespectsCustomUrlKey(): void
    {
        $this->tinyUrlsApi->setUrlKey('mydomain');
        $tinyUrl = $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        self::assertMatchesRegularExpression(
            '/http:\/\/.+\/\?eID=tx_tinyurls&tx_tinyurls\[key\]=mydomain/',
            $tinyUrl,
        );
    }

    public function testApiSetsComment(): void
    {
        $this->tinyUrlsApi->setComment('My test comment');
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertSame('My test comment', $tinyUrlRow['comment']);
    }

    public function testApiSetsDeleteOnUseIfConfiguredInTypoScript(): void
    {
        $typoScript = [
            'tinyurl.' => ['deleteOnUse' => 1],
        ];
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->tinyUrlsApi->initializeConfigFromTyposcript($typoScript, $contentObject);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertNotEmpty($tinyUrlRow['delete_on_use']);
    }

    public function testApiSetsDeleteOnUseIfRequested(): void
    {
        $this->tinyUrlsApi->setDeleteOnUse(true);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertNotEmpty($tinyUrlRow['delete_on_use']);
    }

    public function testApiSetsValidationDateIfRequested(): void
    {
        $validUntilTimestamp = time() + 2000;
        $this->tinyUrlsApi->setValidUntil($validUntilTimestamp);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertSame($validUntilTimestamp, (int)$tinyUrlRow['valid_until']);
    }

    public function testApiSetsValidUntilIfConfiguredInTypoScript(): void
    {
        $validUntilTimestamp = time() + 1000;
        $typoScript = [
            'tinyurl.' => ['validUntil' => $validUntilTimestamp],
        ];
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->tinyUrlsApi->initializeConfigFromTyposcript($typoScript, $contentObject);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        self::assertSame($validUntilTimestamp, (int)$tinyUrlRow['valid_until']);
    }
}
