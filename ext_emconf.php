<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'URL shortener',
	'description' => 'This extensions allows you to cut down long URLs. It basically works like bitly or TinyURL.',
	'category' => 'plugin',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author' => 'Alexander Stehlik',
	'author_email' => 'alexander.stehlik.deleteme@gmail.com',
	'author_company' => '',
	'version' => '0.2.0-dev',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.3-7.4.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);