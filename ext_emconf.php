<?php

########################################################################
# Extension Manager/Repository config file for ext "tinyurls".
#
# Auto generated 23-09-2012 20:44
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'URL shortener',
	'description' => 'This extensions allows you to cut down long URLs. It basically works like bitly or TinyURL.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.0.2',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Alexander Stehlik',
	'author_email' => 'alexander.stehlik.deleteme@googlemail.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.5.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:16:"ext_autoload.php";s:4:"1f47";s:21:"ext_conf_template.txt";s:4:"5953";s:12:"ext_icon.gif";s:4:"81e8";s:17:"ext_localconf.php";s:4:"39f3";s:14:"ext_tables.php";s:4:"0cca";s:14:"ext_tables.sql";s:4:"bb83";s:30:"Classes/Hooks/EidProcessor.php";s:4:"9a42";s:21:"Classes/Hooks/Tce.php";s:4:"bef5";s:26:"Classes/Hooks/TypoLink.php";s:4:"783f";s:23:"Classes/TinyUrl/Api.php";s:4:"402a";s:36:"Classes/TinyUrl/TinyUrlGenerator.php";s:4:"e7a0";s:29:"Classes/Utils/ConfigUtils.php";s:4:"d7f9";s:26:"Classes/Utils/UrlUtils.php";s:4:"ab2f";s:21:"Configuration/Tca.php";s:4:"0868";s:14:"doc/manual.sxw";s:4:"f62f";}',
	'suggests' => array(
	),
);

?>