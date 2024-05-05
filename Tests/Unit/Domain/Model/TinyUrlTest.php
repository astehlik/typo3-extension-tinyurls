<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Unit\Domain\Model;

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
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

class TinyUrlTest extends TestCase
{
    public function testCreateFromDatabaseFillsComment(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame('The cool comment', $tinyUrl->getComment());
    }

    public function testCreateFromDatabaseFillsCounter(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame(4323237, $tinyUrl->getCounter());
    }

    public function testCreateFromDatabaseFillsDeleteOnUse(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertFalse($tinyUrl->getDeleteOnUse());
    }

    public function testCreateFromDatabaseFillsPid(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame(48, $tinyUrl->getPid());
    }

    public function testCreateFromDatabaseFillsTargetUrl(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame('http://the-target.url', $tinyUrl->getTargetUrl());
    }

    public function testCreateFromDatabaseFillsTargetUrlHash(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame('59d24fcbfb6c586daeabf73ddd58cd01affb56d4', $tinyUrl->getTargetUrlHash());
    }

    public function testCreateFromDatabaseFillsTstamp(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame(
            (new DateTime('2017-12-10 12:30:00.000000+0000'))->getTimestamp(),
            $tinyUrl->getTstamp()->getTimestamp(),
        );
    }

    public function testCreateFromDatabaseFillsUid(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame(945, $tinyUrl->getUid());
    }

    public function testCreateFromDatabaseFillsUrlkey(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame('theurlkey', $tinyUrl->getUrlkey());
    }

    public function testCreateFromDatabaseFillsValidUntil(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertSame(
            (new DateTime('2017-10-10 12:30:00.000000+0000'))->getTimestamp(),
            $tinyUrl->getValidUntil()->getTimestamp(),
        );
    }

    public function testCreateFromDatabaseFillsValidUntilWithNullIfEmtpy(): void
    {
        $databaseRow = $this->getDummyDatabaseRow();
        $databaseRow['valid_until'] = 0;
        $tinyUrl = TinyUrl::createFromDatabaseRow($databaseRow);
        self::assertFalse($tinyUrl->hasValidUntil());
    }

    public function testEnableDeleteOnUseSetsDeleteOnUseToTrue(): void
    {
        $tinyUrl = TinyUrl::createNew();
        self::assertFalse($tinyUrl->getDeleteOnUse());
        $tinyUrl->enableDeleteOnUse();
        self::assertTrue($tinyUrl->getDeleteOnUse());
        $tinyUrl->disableDeleteOnUse();
        self::assertFalse($tinyUrl->getDeleteOnUse());
    }

    public function testEqualsReturnsFalseIfBothTinyUrlsAreNewAndAreNotTheSameObject(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl2 = TinyUrl::createNew();
        // @extensionScannerIgnoreLine
        self::assertFalse($tinyUrl->equals($tinyUrl2));
    }

    public function testEqualsReturnsFalseIfBothTinyUrlsExistAndHaveDifferentSameUids(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3);
        $tinyUrl2 = TinyUrl::createNew();
        $tinyUrl2->persistPostProcessInsert(4);
        // @extensionScannerIgnoreLine
        self::assertFalse($tinyUrl->equals($tinyUrl2));
    }

    public function testEqualsReturnsFalseIfOnyTinyUrlIsNewAndTheOtherExists(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl2 = TinyUrl::createNew();
        $tinyUrl2->persistPostProcessInsert(3);
        // @extensionScannerIgnoreLine
        self::assertFalse($tinyUrl->equals($tinyUrl2));
    }

    public function testEqualsReturnsTrueIfBothTinyUrlsAreNewAndAreTheSameObject(): void
    {
        $tinyUrl = TinyUrl::createNew();
        self::assertTrue($tinyUrl->equals($tinyUrl));
    }

    public function testEqualsReturnsTrueIfBothTinyUrlsExistAndHaveTheSameUid(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3);
        $tinyUrl2 = TinyUrl::createNew();
        $tinyUrl2->persistPostProcessInsert(3);
        // @extensionScannerIgnoreLine
        self::assertTrue($tinyUrl->equals($tinyUrl2));
    }

    public function testGetTargetUrlHasChangedReturnsFalseIfUrlWasNotChanged(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        self::assertFalse($tinyUrl->getTargetUrlHasChanged());
    }

    public function testGetTargetUrlHasChangedReturnsTrueIfUrlWasChanged(): void
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $tinyUrl->setTargetUrl('The new target URL');
        self::assertTrue($tinyUrl->getTargetUrlHasChanged());
    }

    public function testHasCustomUrlKeyReturnsFalseForNewRecord(): void
    {
        $tinyUrl = TinyUrl::createNew();
        self::assertFalse($tinyUrl->hasCustomUrlKey());
    }

    public function testHasCustomUrlKeyReturnsTrueIfCustomUrlSet(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('asdf');
        self::assertTrue($tinyUrl->hasCustomUrlKey());
    }

    public function testHasValidUntilReturnsFalseForNewRecord(): void
    {
        $tinyUrl = TinyUrl::createNew();
        self::assertFalse($tinyUrl->hasValidUntil());
    }

    public function testHasValidUntilReturnsTrueIfValidUntilSet(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setValidUntil(new DateTime());
        self::assertTrue($tinyUrl->hasValidUntil());
        $tinyUrl->resetValidUntil();
        self::assertFalse($tinyUrl->hasValidUntil());
    }

    public function testIsNewReturnsTrueForNewTinyUrl(): void
    {
        $tinyUrl = TinyUrl::createNew();
        self::assertTrue($tinyUrl->isNew());
    }

    public function testIsNewReturnsTrueIfUidIsGreaterThanZero(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(2);
        self::assertFalse($tinyUrl->isNew());
    }

    public function testPersistPostProcessInsertResetsOriginalHash(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl('the url');
        self::assertTrue($tinyUrl->getTargetUrlHasChanged());

        $tinyUrl->persistPostProcessInsert(2);
        self::assertFalse($tinyUrl->getTargetUrlHasChanged());
    }

    public function testPersistPostProcessInsertSetsUid(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(2);
        self::assertSame(2, $tinyUrl->getUid());
    }

    public function testPersistPostProcessRefusesZeroUid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(0);
    }

    public function testPersistPostProcessResetsCustomUrlKey(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('custom key');
        self::assertTrue($tinyUrl->hasCustomUrlKey());
        $tinyUrl->persistPostProcess();
        self::assertFalse($tinyUrl->hasCustomUrlKey());
    }

    public function testPersistPostProcessResetsOriginalHash(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl('the url');
        self::assertTrue($tinyUrl->getTargetUrlHasChanged());

        $tinyUrl->persistPostProcess();
        self::assertFalse($tinyUrl->getTargetUrlHasChanged());
    }

    public function testPersistPreProcessSetsCustomUrlKey(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('thekey');
        $tinyUrl->persistPreProcess();
        self::assertSame('thekey', $tinyUrl->getUrlkey());
    }

    public function testPersistPreProcessSetsTstamp(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPreProcess();
        self::assertInstanceOf(DateTimeInterface::class, $tinyUrl->getTstamp());
    }

    public function testResetCustomUrlKeySetsCustomUrlKeyToNull(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('custom key');
        $tinyUrl->resetCustomUrlKey();
        self::assertNull($tinyUrl->getCustomUrlKey());
    }

    public function testSetCommentSetsComment(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setComment('the comment');
        self::assertSame('the comment', $tinyUrl->getComment());
    }

    public function testSetCustomUrlKeyThrowsExceptionIfKeyIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('');
    }

    public function testSetGeneratedUrlKeySetsUrlKey(): void
    {
        $tinyUrl = TinyUrl::createNew();

        $expectedKey = 'the generated url key';

        $tinyUrl->setGeneratedUrlKey($expectedKey);

        self::assertSame($expectedKey, $tinyUrl->getUrlkey());
    }

    public function testSetPidSetsPid(): void
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setPid(103);
        self::assertSame(103, $tinyUrl->getPid());
    }

    protected function getDummyDatabaseRow(): array
    {
        return [
            'uid' => '945',
            'pid' => '48',
            'tstamp' => '1512909000',
            'counter' => '4323237',
            'comment' => 'The cool comment',
            'urlkey' => 'theurlkey',
            'target_url' => 'http://the-target.url',
            'target_url_hash' => sha1('http://the-target.url'),
            'delete_on_use' => '0',
            'valid_until' => '1507638600',
        ];
    }
}
