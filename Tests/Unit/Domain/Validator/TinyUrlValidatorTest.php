<?php
declare(strict_types = 1);
namespace Tx\Tinyurls\Tests\Unit\Domain\Validator;

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
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Domain\Validator\TinyUrlValidator;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;

class TinyUrlValidatorTest extends TestCase
{
    /**
     * @var TinyUrlRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tinyUrlRepositoryMock;

    /**
     * @var TinyUrlValidator
     */
    protected $tinyUrlValidator;

    protected function setUp()
    {
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);
        $this->tinyUrlValidator = new TinyUrlValidator();
        $this->tinyUrlValidator->injectTinyUrlRepository($this->tinyUrlRepositoryMock);
    }

    public function testValidateReturnsErrorIdValidUntilIsInThePast()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setValidUntil(new \DateTime('2000-08-10'));
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        $this->assertEquals(1488307858, $result->forProperty('validUntil')->getFirstError()->getCode());
    }

    public function testValidateReturnsErrorIfCustomUrlKeyExists()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('the custom key');
        $existingTinyUrl = TinyUrl::createNew();
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->with('the custom key')
            ->willReturn($existingTinyUrl);
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        $this->assertEquals(1488317930, $result->forProperty('customUrlKey')->getFirstError()->getCode());
    }

    public function testValidateReturnsNoErrorIdValidUntilIsInTheFuture()
    {
        $tomorrow = new \DateTime();
        $tomorrow->add(\DateInterval::createFromDateString('tomorrow'));
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setValidUntil($tomorrow);
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        $this->assertEmpty($result->forProperty('validUntil')->getFlattenedErrors());
    }

    public function testValidateReturnsNoErrorIfCustomUrlKeyDoesNotExist()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('the custom key');
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->with('the custom key')
            ->willThrowException(new TinyUrlNotFoundException());
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        $this->assertEmpty($result->forProperty('customUrlKey')->getFlattenedErrors());
    }

    public function testValidateReturnsNoErrorIfCustomUrlKeyExistsAndBelongsToSameUrl()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('the custom key');
        $tinyUrl->persistPostProcessInsert(2);
        $existingTinyUrl = TinyUrl::createNew();
        $existingTinyUrl->persistPostProcessInsert(2);
        $this->tinyUrlRepositoryMock->expects($this->once())
            ->method('findTinyUrlByKey')
            ->with('the custom key')
            ->willReturn($existingTinyUrl);
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        $this->assertEmpty($result->forProperty('customUrlKey')->getFlattenedErrors());
    }
}
