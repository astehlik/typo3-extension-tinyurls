<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Acceptance\Support\Helper;

use Tx\Tinyurls\Tests\Acceptance\Support\BackendTester;
use TYPO3\TestingFramework\Core\Acceptance\Helper\AbstractModalDialog;

class ModalDialog extends AbstractModalDialog
{
    public function __construct(BackendTester $I)
    {
        $this->tester = $I;
    }
}
