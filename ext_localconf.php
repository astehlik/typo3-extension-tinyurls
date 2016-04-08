<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// register the eID processor
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_tinyurls'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/EidProcessor.php';

// register the hook for converting typolinks to tinyurls
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']['tx_tinyurls'] = \Tx\Tinyurls\Hooks\TypoLink::class . '->convertTypolinkToTinyUrl';

// register the TYPO3 Backend hook class
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = Tx\Tinyurls\Hooks\TceDataMap::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1460107841] = [
	'nodeName' => 'tx_tinyurls_urldisplay',
	'priority' => 40,
	'class' => \Tx\Tinyurls\Hooks\UrlDisplayFormElement::class,
];