<?php

declare(strict_types=1);

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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Domain\Repository\TinyUrlRepository;
use Tx\Tinyurls\Domain\Validator\TinyUrlValidator;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use DateInterval;
use DateTime;

class TinyUrlValidatorTest extends TestCase
{
    private MockObject|TinyUrlRepository $tinyUrlRepositoryMock;

    private TinyUrlValidator $tinyUrlValidator;

    protected function setUp(): void
    {
        $this->tinyUrlRepositoryMock = $this->createMock(TinyUrlRepository::class);
        $this->tinyUrlValidator = new TinyUrlValidator($this->tinyUrlRepositoryMock);
    }

    public function testGetOptionsReturnsEmptyArray(): void
    {
        // @extensionScannerIgnoreLine
        self::assertSame([], $this->tinyUrlValidator->getOptions());
    }

    public function testValidateReturnsErrorIdValidUntilIsInThePast(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setValidUntil(new DateTime('2000-08-10'));
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        self::assertSame(1488307858, $result->forProperty('validUntil')->getFirstError()->getCode());
    }

    public function testValidateReturnsErrorIfCustomUrlKeyExists(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('the custom key');
        $existingTinyUrl = TinyUrl::createNew();
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->with('the custom key')
            ->willReturn($existingTinyUrl);
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        self::assertSame(1488317930, $result->forProperty('customUrlKey')->getFirstError()->getCode());
    }

    public function testValidateReturnsNoErrorIdValidUntilIsInTheFuture(): void
    {
        $tomorrow = new DateTime();
        $tomorrow->add(DateInterval::createFromDateString('tomorrow'));
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setValidUntil($tomorrow);
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        self::assertEmpty($result->forProperty('validUntil')->getFlattenedErrors());
    }

    public function testValidateReturnsNoErrorIfCustomUrlKeyDoesNotExist(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('the custom key');
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->with('the custom key')
            ->willThrowException(new TinyUrlNotFoundException());
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        self::assertEmpty($result->forProperty('customUrlKey')->getFlattenedErrors());
    }

    public function testValidateReturnsNoErrorIfCustomUrlKeyExistsAndBelongsToSameUrl(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(2);
        $tinyUrl->setCustomUrlKey('the custom key');
        $existingTinyUrl = TinyUrl::createNew();
        $existingTinyUrl->persistPostProcessInsert(2);
        $this->tinyUrlRepositoryMock->expects(self::once())
            ->method('findTinyUrlByKey')
            ->with('the custom key')
            ->willReturn($existingTinyUrl);
        $result = $this->tinyUrlValidator->validate($tinyUrl);
        self::assertEmpty($result->forProperty('customUrlKey')->getFlattenedErrors());
    }
}
