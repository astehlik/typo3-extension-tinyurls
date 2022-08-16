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
    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/tinyurls'];

    /**
     * @var Api
     */
    protected $tinyUrlsApi;

    /**
     * Initializes the test subject.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tinyUrlsApi = GeneralUtility::makeInstance(Api::class);
    }

    /**
     * @test
     */
    public function apiDoesNotSetDeleteOnUseByDefault()
    {
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        $this->assertEmpty($tinyUrlRow['delete_on_use']);
    }

    /**
     * @test
     */
    public function apiDoesNotSetValidationDateByDefault()
    {
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        $this->assertEmpty($tinyUrlRow['valid_until']);
    }

    /**
     * @test
     */
    public function apiRespectsCustomUrlKey()
    {
        $this->tinyUrlsApi->setUrlKey('mydomain');
        $tinyUrl = $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $this->assertRegExp(
            '/http:\/\/.+\/\?eID=tx_tinyurls&tx_tinyurls\[key\]=mydomain/',
            $tinyUrl
        );
    }

    /**
     * @test
     */
    public function apiSetsComment()
    {
        $this->tinyUrlsApi->setComment('My test comment');
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        $this->assertEquals('My test comment', $tinyUrlRow['comment']);
    }

    /**
     * @test
     */
    public function apiSetsDeleteOnUseIfConfiguredInTypoScript()
    {
        $typoScript = [
            'tinyurl.' => ['deleteOnUse' => 1],
        ];
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->tinyUrlsApi->initializeConfigFromTyposcript($typoScript, $contentObject);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        $this->assertNotEmpty($tinyUrlRow['delete_on_use']);
    }

    /**
     * @test
     */
    public function apiSetsDeleteOnUseIfRequested()
    {
        $this->tinyUrlsApi->setDeleteOnUse(true);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        $this->assertNotEmpty($tinyUrlRow['delete_on_use']);
    }

    /**
     * @test
     */
    public function apiSetsValidUntilIfConfiguredInTypoScript()
    {
        $validUntilTimestamp = time() + 1000;
        $typoScript = [
            'tinyurl.' => ['validUntil' => $validUntilTimestamp],
        ];
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->tinyUrlsApi->initializeConfigFromTyposcript($typoScript, $contentObject);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        $this->assertEquals($validUntilTimestamp, $tinyUrlRow['valid_until']);
    }

    /**
     * @test
     */
    public function apiSetsValidationDateIfRequested()
    {
        $validUntilTimestamp = time() + 2000;
        $this->tinyUrlsApi->setValidUntil($validUntilTimestamp);
        $this->tinyUrlsApi->getTinyUrl('http://mydomain.tld');
        $tinyUrlRow = $this->getTinyUrlRow();
        $this->assertEquals($validUntilTimestamp, $tinyUrlRow['valid_until']);
    }
}
