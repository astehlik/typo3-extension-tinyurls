<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

defined('TYPO3') or die();

// Register the eID processor.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_tinyurls'] =
    \Tx\Tinyurls\Controller\EidController::class . '::tinyUrlRedirect';

// Register the TYPO3 Backend hook class.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    Tx\Tinyurls\Hooks\TceDataMap::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1460107841] = [
    'nodeName' => 'tx_tinyurls_copyable_field',
    'priority' => 40,
    'class' => \Tx\Tinyurls\FormEngine\CopyableFieldElement::class,
];
