.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

.. _configuration-typoscript:

TypoScript Reference
--------------------

These additional properties are available for typolink objects when the extension is installed:

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         tinyurl

   Data type
         boolean

   Description
         If you set this to 1 (TRUE) the final typolink URL is converted to a tiny URL.

         You an set some configuration options for the tiny URL generator int the :typoscript:`tinyurl.` namespace (see below).

   Default
         0

.. container:: table-row

   Property
         tinyurl.deleteOnUse

   Data type
         boolean /:ref:`stdWrap <stdwrap>`

   Description
         If this is is true, the tiny URL is deleted from the database on the first hit.
         This makes the most sense when used together with the urlKey property.

   Default
         0

.. container:: table-row

   Property
         tinyurl.validUntil

   Data type
         int /:ref:`stdWrap <stdwrap>`

   Description
         The timestamp until the URL is valid. If this is set to 0 the URL will never be invalid.

   Default
         0

.. container:: table-row

   Property
         tinyurl.urlKey

   Data type
         string /:ref:`stdWrap <stdwrap>`

   Description
         Normally the URL key is generated automatically. Here you can set you own unique urlKey.
         This may be used together with the deleteOnUse property for creating one-time valid URLs,
         e.g. for activating a subscription.

   Default
         0

.. ###### END~OF~TABLE ######
