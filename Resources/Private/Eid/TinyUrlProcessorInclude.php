<?php
use Tx\Tinyurls\Hooks\EidProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$eidProcessor = GeneralUtility::makeInstance(EidProcessor::class);
$eidProcessor->main();
