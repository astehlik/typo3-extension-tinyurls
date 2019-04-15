<?php
declare(strict_types=1);

namespace Tx\Tinyurls\Domain\Repository;

/*                                                                        *
 * This script belongs to the TYPO3 extension "tinyurls".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Tx\Tinyurls\Domain\Model\TinyUrl;
use Tx\Tinyurls\Exception\TinyUrlNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;

interface TinyUrlRepository extends SingletonInterface
{
    const TABLE_URLS = 'tx_tinyurls_urls';

    /**
     * See: http://lists.typo3.org/pipermail/typo3-dev/2007-December/026936.html
     * Use of "set counter=counter+1" - avoiding race conditions
     *
     * @param TinyUrl $tinyUrl
     * @return TinyUrl A new tiny URL instance with an updated counter.
     */
    public function countTinyUrlHit(TinyUrl $tinyUrl): TinyUrl;

    /**
     * Deletes the URL with the given URL key.
     *
     * @param string $tinyUrlKey
     * @return void
     */
    public function deleteTinyUrlByKey(string $tinyUrlKey);

    /**
     * Finds the URL by the given URL key.
     *
     * @param string $tinyUrlKey
     * @return TinyUrl
     * @throws TinyUrlNotFoundException
     */
    public function findTinyUrlByKey(string $tinyUrlKey): TinyUrl;

    /**
     * Finds the URL by the given target URL.
     *
     * @param string $targetUrl
     * @return TinyUrl
     * @throws TinyUrlNotFoundException
     */
    public function findTinyUrlByTargetUrl(string $targetUrl): TinyUrl;

    /**
     * Finds the URL by the given UID.
     *
     * @param int $uid
     * @return TinyUrl
     * @throws TinyUrlNotFoundException
     */
    public function findTinyUrlByUid(int $uid): TinyUrl;

    /**
     * Inserts the given URL in the database.
     *
     * @param TinyUrl $tinyUrl
     * @return void
     */
    public function insertNewTinyUrl(TinyUrl $tinyUrl);

    /**
     * Purges all invalid urls from the database
     */
    public function purgeInvalidUrls();

    /**
     * Updates an existing TinyURL in the storage.
     *
     * @param TinyUrl $tinyUrl
     * @return void
     */
    public function updateTinyUrl(TinyUrl $tinyUrl);
}
