.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer:

Developer Corner
================

Using tinyurls in your extension
--------------------------------

When you want to generate tiny URLs in your own extension you can use the :php:`\Tx\Tinyurls\TinyUrl\Api` class.
Please have a look at the PHPDoc annotations for further information. This is a quick example how to use it:

::

	$tinyUrlApi = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Tx\Tinyurls\TinyUrl\Api::class);
	$tinyUrlApi->setDeleteOnUse(1);
	$tinyUrlApi->setUrlKey($myKey);
	$tinyUrlApi->setValidUntil($validUntil);
	$myTinyUrl = $tinyUrlApi->getTinyUrl($url);


