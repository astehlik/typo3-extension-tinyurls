<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Object\ImplementationManager;

class TinyUrl
{
    /**
     * @var string
     */
    protected $comment = '';

    /**
     * @var int
     */
    protected $counter = 0;

    /**
     * @var string
     */
    protected $customUrlKey;

    /**
     * @var bool
     */
    protected $deleteOnUse = false;

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var string
     */
    protected $targetUrl = '';

    /**
     * @var string
     */
    protected $targetUrlHash = '';

    /**
     * The hash hat was originally stored in the database.
     *
     * @var string
     */
    protected $targetUrlHashOriginal = '';

    /**
     * @var \DateTime
     */
    protected $tstamp;

    /**
     * @var int
     */
    protected $uid;

    /**
     * @var string
     */
    protected $urlkey = '';

    /**
     * @var \DateTime
     */
    protected $validUntil;

    public static function createFromDatabaseRow(array $databaseRow): self
    {
        $tinyUrl = new static();
        $tinyUrl->fillFromDatabaseRow($databaseRow);
        return $tinyUrl;
    }

    public static function createNew(): self
    {
        return new static();
    }

    public function enableDeleteOnUse(): void
    {
        $this->deleteOnUse = true;
    }

    public function equals(self $existingTinyUrl): bool
    {
        if ($this->isNew() && $existingTinyUrl->isNew()) {
            return $this === $existingTinyUrl;
        }
        if (!$this->isNew() && !$existingTinyUrl->isNew()) {
            return $this->getUid() === $existingTinyUrl->getUid();
        }
        return false;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function getCustomUrlKey(): string
    {
        return $this->customUrlKey;
    }

    public function getDeleteOnUse(): bool
    {
        return $this->deleteOnUse;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function getTargetUrlHasChanged(): bool
    {
        return $this->targetUrlHashOriginal !== $this->getTargetUrlHash();
    }

    public function getTargetUrlHash(): string
    {
        $this->regenerateTargetUrlHash();
        return $this->targetUrlHash;
    }

    public function getTstamp(): \DateTime
    {
        return $this->tstamp;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getUrlkey(): string
    {
        return $this->urlkey;
    }

    public function getValidUntil(): \DateTime
    {
        return $this->validUntil;
    }

    public function hasCustomUrlKey()
    {
        return $this->customUrlKey !== null;
    }

    public function hasValidUntil(): bool
    {
        return $this->validUntil !== null;
    }

    public function isNew(): bool
    {
        return (int)$this->uid === 0;
    }

    public function persistPostProcess(): void
    {
        $this->customUrlKey = null;
        $this->targetUrlHashOriginal = $this->targetUrlHash;
    }

    /**
     * Initialises the UID generated during persistence.
     *
     * IMPORTANT! The Repository needs to update the record in the persistence once more
     * if no custom URL key is used because we can not generate the URL key without a UID.
     */
    public function persistPostProcessInsert(int $newUid): void
    {
        if ($newUid === 0) {
            throw new \InvalidArgumentException('The inserted UID must not be zero.');
        }
        $this->uid = $newUid;
        $this->persistPostProcess();
    }

    public function persistPreProcess(): void
    {
        if ($this->hasCustomUrlKey()) {
            $this->urlkey = $this->getCustomUrlKey();
        }
        $this->tstamp = new \DateTime();
    }

    public function regenerateUrlKey(): void
    {
        $tinyUrlKeyGenerator = ImplementationManager::getInstance()->getUrlKeyGenerator();
        $this->urlkey = $tinyUrlKeyGenerator->generateTinyurlKeyForTinyUrl($this);
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function setCustomUrlKey(string $customUrlKey): void
    {
        $customUrlKey = trim($customUrlKey);

        if ($customUrlKey === '') {
            throw new \InvalidArgumentException('Using an empty custom URL key is not allowed.');
        }

        $this->customUrlKey = $customUrlKey;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function setTargetUrl(string $targetUrl): void
    {
        $this->targetUrl = $targetUrl;
    }

    public function setValidUntil(\DateTime $validUntil): void
    {
        $this->validUntil = $validUntil;
    }

    protected function fillFromDatabaseRow(array $databaseRow): void
    {
        $this->uid = (int)$databaseRow['uid'];
        $this->pid = (int)$databaseRow['pid'];
        $this->tstamp = new \DateTime('@' . (int)$databaseRow['tstamp']);
        $this->counter = (int)$databaseRow['counter'];
        $this->comment = (string)$databaseRow['comment'];
        $this->urlkey = (string)$databaseRow['urlkey'];
        $this->targetUrl = (string)$databaseRow['target_url'];
        $this->targetUrlHash = (string)$databaseRow['target_url_hash'];
        $this->targetUrlHashOriginal = (string)$databaseRow['target_url_hash'];
        $this->deleteOnUse = (bool)$databaseRow['delete_on_use'];
        $this->validUntil = (int)$databaseRow['valid_until'] !== 0
            ? new \DateTime('@' . (int)$databaseRow['valid_until'])
            : null;
    }

    protected function regenerateTargetUrlHash(): void
    {
        $this->targetUrlHash = sha1($this->getTargetUrl());
    }
}
