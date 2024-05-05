.. include:: ../Includes.txt

.. _configuration:

Configuration Reference
=======================

Most of the configuration is done in the extension configuration.

**New in version 13.x**: Every Extension configuration setting can now be overwritten in the Site Configuration
in the :code:`tinyurls` key, e.g.:

.. code-block:: yaml

    tinyurls:
      baseUrl: 'https://example.com'

When using the typolink configuration, there are also additional TypoScript settings available.

.. toctree::
   :maxdepth: 3

   ExtensionConfiguration/Index
   TypoScriptReference/Index
