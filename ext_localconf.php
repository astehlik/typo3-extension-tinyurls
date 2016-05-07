<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Register the eID processor.
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_tinyurls'] =
    'EXT:' . $_EXTKEY . '/Resources/Private/Eid/TinyUrlProcessorInclude.php';

// Register the hook for converting typolinks to tinyurls.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']['tx_tinyurls'] =
    \Tx\Tinyurls\Hooks\TypoLink::class . '->convertTypolinkToTinyUrl';

// Register the TYPO3 Backend hook class.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    Tx\Tinyurls\Hooks\TceDataMap::class;
