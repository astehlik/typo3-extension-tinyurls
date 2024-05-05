.. _developer:

Developer Corner
================

Using tinyurls in your extension
--------------------------------

When you want to generate tiny URLs in your own extension you can use the :php:`\Tx\Tinyurls\Domain\Model\TinyUrl`
class to set the properties and then use the :php:`\Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface` service
to generate the Tiny URL.

Please have a look at the PHPDoc annotations for further information. This is a quick example how to use it:

.. code-block:: php


	<?php

    use Tx\Tinyurls\Domain\Model\TinyUrl;
    use Tx\Tinyurls\TinyUrl\TinyUrlGeneratorInterface;

    class MyDemo
    {
        public function __construct(private TinyUrlGeneratorInterface $tinyUrlGenerator)
        {
        }

        public function createTinyUrl(string $theKey, DateTimeInterface $validUntil): string
        {
            $tinyUrl = TinyUrl::createForUrl('http://www.typo3.org');
            $tinyUrl->enableDeleteOnUse();
            $tinyUrl->setCustomUrlKey($theKey);
            $tinyUrl->setValidUntil($validUntil);

            return $this->tinyUrlGenerator->generateTinyUrl($tinyUrl);
        }
    }
