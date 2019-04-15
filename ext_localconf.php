<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Register the eID processor.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_tinyurls'] =
    \Tx\Tinyurls\Controller\EidController::class . '::tinyUrlRedirect';

// Register the hook for converting typolinks to tinyurls.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']['tx_tinyurls'] =
    \Tx\Tinyurls\Hooks\TypoLink::class . '->convertTypolinkToTinyUrl';

// Register the TYPO3 Backend hook class.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    Tx\Tinyurls\Hooks\TceDataMap::class;

// Modify the rendering of the list module.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray']['tx_tinyurls'] =
    \Tx\Tinyurls\Hooks\DatabaseRecordList::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1460107841] = [
    'nodeName' => 'tx_tinyurls_copyable_field',
    'priority' => 40,
    'class' => \Tx\Tinyurls\FormEngine\CopyableFieldElement::class,
];
