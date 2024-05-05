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

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class TinyUrl
{
    protected string $comment = '';

    protected int $counter = 0;

    protected ?string $customUrlKey = null;

    protected bool $deleteOnUse = false;

    protected int $pid = 0;

    protected string $targetUrl = '';

    protected string $targetUrlHash = '';

    protected string $targetUrlHashOriginal = '';

    protected DateTimeInterface $tstamp;

    protected int $uid = 0;

    protected string $urlkey = '';

    protected ?DateTimeInterface $validUntil = null;

    /**
     * The consturctor is final because new static() is used
     * and we want to prevent changes to the method signature.
     */
    final public function __construct() {}

    /**
     * @param non-empty-string $url
     */
    public static function createForUrl(string $url): self
    {
        $tinyUrl = new static();
        $tinyUrl->setTargetUrl($url);
        return $tinyUrl;
    }

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

    public function disableDeleteOnUse(): void
    {
        $this->deleteOnUse = false;
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

    public function getCustomUrlKey(): ?string
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

    public function getTstamp(): DateTimeInterface
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

    public function getValidUntil(): DateTimeInterface
    {
        return $this->validUntil;
    }

    public function hasCustomUrlKey(): bool
    {
        return $this->customUrlKey !== null;
    }

    public function hasValidUntil(): bool
    {
        return $this->validUntil !== null;
    }

    public function isNew(): bool
    {
        return $this->uid === 0;
    }

    public function persistPostProcess(): void
    {
        $this->resetCustomUrlKey();
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
            throw new InvalidArgumentException('The inserted UID must not be zero.');
        }
        $this->uid = $newUid;
        $this->persistPostProcess();
    }

    public function persistPreProcess(): void
    {
        if ($this->hasCustomUrlKey()) {
            $this->urlkey = $this->getCustomUrlKey();
        }
        $this->tstamp = new DateTimeImmutable();
    }

    public function resetCustomUrlKey(): void
    {
        $this->customUrlKey = null;
    }

    public function resetValidUntil(): void
    {
        $this->validUntil = null;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function setCustomUrlKey(string $customUrlKey): void
    {
        $customUrlKey = trim($customUrlKey);

        if ($customUrlKey === '') {
            throw new InvalidArgumentException('Using an empty custom URL key is not allowed.');
        }

        $this->customUrlKey = $customUrlKey;
    }

    public function setGeneratedUrlKey(string $urlkey): void
    {
        $this->urlkey = $urlkey;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function setTargetUrl(string $targetUrl): void
    {
        $this->targetUrl = $targetUrl;
    }

    public function setValidUntil(DateTimeInterface $validUntil): void
    {
        $this->validUntil = $validUntil;
    }

    protected function fillFromDatabaseRow(array $databaseRow): void
    {
        $this->uid = (int)$databaseRow['uid'];
        $this->pid = (int)$databaseRow['pid'];
        $this->tstamp = new DateTimeImmutable('@' . (int)$databaseRow['tstamp']);
        $this->counter = (int)$databaseRow['counter'];
        $this->comment = (string)$databaseRow['comment'];
        $this->urlkey = (string)$databaseRow['urlkey'];
        $this->targetUrl = (string)$databaseRow['target_url'];
        $this->targetUrlHash = (string)$databaseRow['target_url_hash'];
        $this->targetUrlHashOriginal = (string)$databaseRow['target_url_hash'];
        $this->deleteOnUse = (bool)$databaseRow['delete_on_use'];
        $this->validUntil = (int)$databaseRow['valid_until'] !== 0
            ? new DateTimeImmutable('@' . (int)$databaseRow['valid_until'])
            : null;
    }

    protected function regenerateTargetUrlHash(): void
    {
        $this->targetUrlHash = sha1($this->getTargetUrl());
    }
}
