<?php
declare(strict_types = 1);
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

class TinyUrlTest extends TestCase
{
    public function testCreateFromDatabaseFillsComment()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals('The cool comment', $tinyUrl->getComment());
    }

    public function testCreateFromDatabaseFillsCounter()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals(4323237, $tinyUrl->getCounter());
    }

    public function testCreateFromDatabaseFillsDeleteOnUse()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals(false, $tinyUrl->getDeleteOnUse());
    }

    public function testCreateFromDatabaseFillsPid()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals(48, $tinyUrl->getPid());
    }

    public function testCreateFromDatabaseFillsTargetUrl()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals('http://the-target.url', $tinyUrl->getTargetUrl());
    }

    public function testCreateFromDatabaseFillsTargetUrlHash()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals('59d24fcbfb6c586daeabf73ddd58cd01affb56d4', $tinyUrl->getTargetUrlHash());
    }

    public function testCreateFromDatabaseFillsTstamp()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals(new \DateTime('2017-12-10 12:30:00.000000+0000'), $tinyUrl->getTstamp());
    }

    public function testCreateFromDatabaseFillsUid()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals(945, $tinyUrl->getUid());
    }

    public function testCreateFromDatabaseFillsUrlkey()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals('theurlkey', $tinyUrl->getUrlkey());
    }

    public function testCreateFromDatabaseFillsValidUntil()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertEquals(new \DateTime('2017-10-10 12:30:00.000000+0000'), $tinyUrl->getValidUntil());
    }

    public function testEqualsReturnsFalseIfBothTinyUrlsAreNewAndAreNotTheSameObject()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl2 = TinyUrl::createNew();
        $this->assertFalse($tinyUrl->equals($tinyUrl2));
    }

    public function testEqualsReturnsFalseIfBothTinyUrlsExistAndHaveDifferentSameUids()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3);
        $tinyUrl2 = TinyUrl::createNew();
        $tinyUrl2->persistPostProcessInsert(4);
        $this->assertFalse($tinyUrl->equals($tinyUrl2));
    }

    public function testEqualsReturnsFalseIfOnyTinyUrlIsNewAndTheOtherExists()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl2 = TinyUrl::createNew();
        $tinyUrl2->persistPostProcessInsert(3);
        $this->assertFalse($tinyUrl->equals($tinyUrl2));
    }

    public function testEqualsReturnsTrueIfBothTinyUrlsAreNewAndAreTheSameObject()
    {
        $tinyUrl = TinyUrl::createNew();
        $this->assertTrue($tinyUrl->equals($tinyUrl));
    }

    public function testEqualsReturnsTrueIfBothTinyUrlsExistAndHaveTheSameUid()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(3);
        $tinyUrl2 = TinyUrl::createNew();
        $tinyUrl2->persistPostProcessInsert(3);
        $this->assertTrue($tinyUrl->equals($tinyUrl2));
    }

    public function testGetTargetUrlHasChangedReturnsFalseIfUrlWasNotChanged()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $this->assertFalse($tinyUrl->getTargetUrlHasChanged());
    }

    public function testGetTargetUrlHasChangedReturnsTrueIfUrlWasChanged()
    {
        $tinyUrl = TinyUrl::createFromDatabaseRow($this->getDummyDatabaseRow());
        $tinyUrl->setTargetUrl('The new target URL');
        $this->assertTrue($tinyUrl->getTargetUrlHasChanged());
    }

    public function testHasCustomUrlKeyReturnsFalseForNewRecord()
    {
        $tinyUrl = TinyUrl::createNew();
        $this->assertFalse($tinyUrl->hasCustomUrlKey());
    }

    public function testHasCustomUrlKeyReturnsTrueIfCustomUrlSet()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('asdf');
        $this->assertTrue($tinyUrl->hasCustomUrlKey());
    }

    public function testHasValidUntilReturnsFalseForNewRecord()
    {
        $tinyUrl = TinyUrl::createNew();
        $this->assertFalse($tinyUrl->hasValidUntil());
    }

    public function testHasValidUntilReturnsTrueIfValidUntilSet()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setValidUntil(new \DateTime());
        $this->assertTrue($tinyUrl->hasValidUntil());
    }

    public function testIsNewReturnsTrueForNewTinyUrl()
    {
        $tinyUrl = TinyUrl::createNew();
        $this->assertTrue($tinyUrl->isNew());
    }

    public function testIsNewReturnsTrueIfUidIsGreaterThanZero()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(2);
        $this->assertFalse($tinyUrl->isNew());
    }

    public function testPersistPostProcessInsertResetsOriginalHash()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl('the url');
        $this->assertTrue($tinyUrl->getTargetUrlHasChanged());

        $tinyUrl->persistPostProcessInsert(2);
        $this->assertFalse($tinyUrl->getTargetUrlHasChanged());
    }

    public function testPersistPostProcessInsertSetsUid()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(2);
        $this->assertEquals(2, $tinyUrl->getUid());
    }

    public function testPersistPostProcessRefusesZeroUid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(0);
    }

    public function testPersistPostProcessResetsCustomUrlKey()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('custom key');
        $this->assertTrue($tinyUrl->hasCustomUrlKey());
        $tinyUrl->persistPostProcess();
        $this->assertFalse($tinyUrl->hasCustomUrlKey());
    }

    public function testPersistPostProcessResetsOriginalHash()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setTargetUrl('the url');
        $this->assertTrue($tinyUrl->getTargetUrlHasChanged());

        $tinyUrl->persistPostProcess();
        $this->assertFalse($tinyUrl->getTargetUrlHasChanged());
    }

    public function testPersistPreProcessSetsCustomUrlKey()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('thekey');
        $tinyUrl->persistPreProcess();
        $this->assertEquals('thekey', $tinyUrl->getUrlkey());
    }

    public function testPersistPreProcessSetsTstamp()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPreProcess();
        $this->assertInstanceOf(\DateTime::class, $tinyUrl->getTstamp());
    }

    public function testRegenerateUrlKeySetsUrlKeyProperty()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->persistPostProcessInsert(2);
        $tinyUrl->regenerateUrlKey();
        $this->assertRegExp('/c\-[0-9a-f]+/', $tinyUrl->getUrlkey());
    }

    public function testSetCommentSetsComment()
    {
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setComment('the comment');
        $this->assertEquals('the comment', $tinyUrl->getComment());
    }

    public function testSetCustomUrlKeyThrowsExceptionIfKeyIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $tinyUrl = TinyUrl::createNew();
        $tinyUrl->setCustomUrlKey('');
    }

    protected function getDummyDatabaseRow()
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
