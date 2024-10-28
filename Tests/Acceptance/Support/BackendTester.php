<?php

declare(strict_types=1);

namespace Tx\Tinyurls\Tests\Acceptance\Support;

use Codeception\Actor;
use Tx\Tinyurls\Tests\Acceptance\Support\_generated\BackendTesterActions;
use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;

/**
 * Default backend admin or editor actor in the backend.
 */
class BackendTester extends Actor
{
    use BackendTesterActions;
    use FrameSteps;
}
