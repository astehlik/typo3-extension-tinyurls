<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Utils;

use Tx\Tinyurls\Domain\Model\TinyUrl;

interface UrlUtilsInterface
{
    public function buildTinyUrl(string $tinyUrlKey): string;

    public function buildTinyUrlForPid(string $urlkey, int $pid): string;

    public function regenerateUrlKey(TinyUrl $tinyUrl): void;
}
