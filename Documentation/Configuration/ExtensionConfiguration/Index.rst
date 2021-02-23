.. include:: ../../Includes.txt

.. _configuration-extension-configuration:

Extension configuration
-----------------------

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         createSpeakingURLs

   Data type
         boolean

   Description
         If true (1) then the tiny URL will be generated depending on the speakingUrlTemplate.
         Hint! If you enable this you might need to add a rewrite rule to you webserver!

   Default
         0

.. container:: table-row

   Property
         speakingUrlTemplate

   Data type
         string

   Description
         The template that is used for creating a speaking URL (only relevant if createSpeakingURLs is set to 1).
         You can use all available keys for :code:`\TYPO3\CMS\Core\Utility\GeneraulUtility::getIndpEnv()`
         as template markers (e.g. :php:`###TYPO3_SITE_URL###`  and the :code:`###TINY_URL_KEY###`
         template marker will be replaced with the shortened URL key.

   Default
         :code:`###TYPO3_SITE_URL###tinyurl/###TINY_URL_KEY###`

.. container:: table-row

   Property
         base62Dictionary

   Data type
         string

   Description
         Dictionary for creating bas 62 based integers (see http://jeremygibbs.com/2012/01/16/how-to-make-a-url-shortener),
         use random string to increase security (e.g. http://textmechanic.com /String-Randomizer.html)

   Default
         abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789

.. container:: table-row

   Property
         minimalRandomKeyLength

   Data type
         integer

   Description
         The minimum length that the random part of the tiny URL must have.

   Default
         2

.. container:: table-row

   Property
         minimalTinyurlKeyLength

   Data type
         integer

   Description
         The minimum length that the whole tiny URL key must have.

   Default
         8

.. container:: table-row

   Property
         urlRecordStoragePID

   Data type
         integer

   Description
         The PID where the tiny URL records are stored, use 0 to store them in the TYPO3 root.

   Default
         8

.. ###### END~OF~TABLE ######
