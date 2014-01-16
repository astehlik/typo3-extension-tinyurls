<?php

if (!defined('TYPO3_MODE')) die ('Access denied.');

	// register the eID processor
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_tinyurls'] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/EidProcessor.php';

	// register the hook for converting typolinks to tinyurls
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']['tx_tinyurls'] = 'Tx_Tinyurls_Hooks_TypoLink->convertTypolinkToTinyUrl';

	// register the TYPO3 Backend hook class
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'tx_tinyurls_hooks_tce';

?>