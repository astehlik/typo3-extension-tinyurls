<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Acceptance\Support\Helper;

use Tx\Tinyurls\Tests\Acceptance\Support\BackendTester;
use De\SWebhosting\Buildtools\Tests\Acceptance\Support\Helper\AbstractPageTree;

class PageTree extends AbstractPageTree
{
    public function __construct(BackendTester $I)
    {
        $this->tester = $I;
    }
}
