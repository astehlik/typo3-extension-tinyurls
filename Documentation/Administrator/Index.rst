.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================

.. _admin-installation:

Installation
------------

The first step, to get this extension up and running is importing it in the extension manager
and install it.

**Alternatively** you can use composer to install the extension:

::

	composer require de-swebhosting-typo3-extension/tinyurls

Then you can edit the **extension configuration** according to your needs.

Now you are able to create and use tiny URLs in your TYPO3 instance.


.. _admin-editing-tinyurl-records:

Editing tiny URL records
------------------------

Once the extension is installed you can add tiny URLs in the TYPO3 backend. If you didn't change the
default value of :code:`urlRecordStoragePID` in the extension configuration you must add the records
in the TYPO3 root. Otherwise you can add them in the page (folder) with the PID you specified in the config.


Using tinyurls in TypoScript
----------------------------

You can convert any typolink to a tinyurl by simply setting the property tinyurl to 1. For more information about the TypoScript configuration options please have a look in the TypoScript configuration section.

This is a quick example:

::

	page.30 = TEXT
	page.30 {
			value = Permalink
			typolink = 1
			typolink.parameter.data = getIndpEnv:REQUEST_URI
			typolink.tinyurl = 1
	}


Further information
-------------------

.. toctree::
	:maxdepth: 5
	:titlesonly:
	:glob:

	SpeakingUrlConfiguration/Index
