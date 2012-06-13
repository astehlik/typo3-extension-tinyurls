<?php
$classesPath = t3lib_extMgm::extPath('tinyurls', 'Classes/');
return array(
	'tx_tinyurls_hooks_tce' => $classesPath . 'Hooks/Tce.php',
	'tx_tinyurls_hooks_typolink' => $classesPath . 'Hooks/TypoLink.php',
	'tx_tinyurls_utils_urlutils' => $classesPath . 'Utils/UrlUtils.php'
);
