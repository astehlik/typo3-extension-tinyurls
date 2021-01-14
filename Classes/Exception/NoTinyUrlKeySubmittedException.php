<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Exception;

use TYPO3\CMS\Core\Error\Http\BadRequestException;

class NoTinyUrlKeySubmittedException extends BadRequestException
{
    public function __construct()
    {
        parent::__construct('No tinyurl key was submitted.');
    }
}
