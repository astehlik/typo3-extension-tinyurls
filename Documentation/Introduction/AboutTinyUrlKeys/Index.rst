.. _introduction-about-tinyurl-keys:

About Tiny URL Keys
===================

A tiny URL key consists of two parts:

1. a base62 encoded integer based on a custom dictionary
2. and an optional random part, seperated by a dash

A URL key might look like this:

::

   Aefc-3E

The first part is generated from the UID of the tiny URL database record which is converted to a base62 integer.

The second part (after the dash) is simply a random hexadecimal string.

In the extension configuration you can edit two options that influence the tiny URL key generation. If
minimalTinyurlKeyLength is set the URL key must have at least this amount of characters. If the base62 part is
shorter the missing characters will be appended. An example:

* minimalTinyurlKeyLength is 6
* the generated base62 part is Aefc
* the URL generator will add two more characters after a dash
* if the minimalTinyurlKeyLength was only 4 no more characters would have been added

Another option is the minimalRandomKeyLength . If this is set to a value greater than zero the defined number
of random characters will always be appended to the base62 part. Another example:

* minimalTinyurlKeyLength is 4
* minimalRandomKeyLength is 3
* the generated base62 part is Aefc
* the URL generator will add three more characters after a dash
