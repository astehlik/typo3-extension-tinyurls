<?php
declare(strict_types = 1);
use Tx\Tinyurls\Controller\EidController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$eidProcessor = GeneralUtility::makeInstance(EidController::class);
$eidProcessor->main();
