<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Unit\ViewHelpers;

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
use Tx\Tinyurls\TinyUrl\Api;
use Tx\Tinyurls\ViewHelpers\TinyurlViewHelper;

class TinyurlViewHelperTest extends TestCase
{
    /**
     * @var Api|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tinyUrlApi;

    /**
     * @var TinyurlViewHelper
     */
    protected $tinyurlViewHelper;

    protected function setUp()
    {
        $this->tinyUrlApi = $this->createMock(Api::class);
        $this->tinyurlViewHelper = new TinyurlViewHelper();
        $this->tinyurlViewHelper->setTinyUrlApi($this->tinyUrlApi);
    }

    public function testCustomUrlKeyIsPassedToTinyUrlApi()
    {
        $this->tinyUrlApi->expects($this->once())
            ->method('setUrlKey')
            ->with('theurl-key')
            ->willReturn('');
        $this->tinyurlViewHelper->render('http://the-url.tld', false, 0, 'theurl-key');
    }

    public function testHtmlCharsInUrlAreEscapedIfRequired()
    {
        $this->tinyUrlApi->expects($this->once())
            ->method('getTinyUrl')
            ->willReturn('http://the-url.tld?test=1&test2=2');

        $result = $this->tinyurlViewHelper->render('http://the-url.tld');

        if (property_exists($this->tinyurlViewHelper, 'escapeOutput')) {
            $this->assertEquals('http://the-url.tld?test=1&test2=2', $result);
        } else {
            $this->assertEquals('http://the-url.tld?test=1&amp;test2=2', $result);
        }
    }

    public function testOnlyOneTimeValidSetsDeleteOnUse()
    {
        $this->tinyUrlApi->expects($this->once())
            ->method('setDeleteOnUse')
            ->with(true)
            ->willReturn('');

        $this->tinyurlViewHelper->render('http://www.url.tld', true);
    }

    public function testUrlIsPassedToTinyUrlApi()
    {
        $this->tinyUrlApi->expects($this->once())
            ->method('getTinyUrl')
            ->with('http://the-url.tld')
            ->willReturn('');
        $this->tinyurlViewHelper->render('http://the-url.tld');
    }

    public function testValidUntilIsPassedToTinyUrlApi()
    {
        $this->tinyUrlApi->expects($this->once())
            ->method('setValidUntil')
            ->with(3848909)
            ->willReturn('');
        $this->tinyurlViewHelper->render('http://the-url.tld', false, 3848909);
    }
}
