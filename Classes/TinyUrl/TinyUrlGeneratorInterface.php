<?php

declare(strict_types=1);

namespace Tx\Tinyurls\TinyUrl;

use Tx\Tinyurls\Domain\Model\TinyUrl;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

interface TinyUrlGeneratorInterface
{
    public function generateTinyUrl(TinyUrl $tinyUrl): string;

    public function generateTinyUrlForSite(TinyUrl $tinyUrl, ?SiteInterface $site): string;
}
