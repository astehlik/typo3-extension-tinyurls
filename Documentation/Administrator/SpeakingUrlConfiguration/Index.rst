.. include:: ../Includes.txt

.. _admin-speaking-url-configuration:

Speaking URL configuration
==========================

By default, a tiny URL created by this extension will look like this:

`<http://mytypo3page.tld/index.php?eID=tx_tinyurls&tx_tinyurls[key ]=Aefc-3E>`_

This is not very short and readable. This is why using speaking URLs is recommended. For this to work you need
to set createSpeakingURLs to 1 and maybe edit the speakingUrlTemplate . By default, speaking URLs will look
like this:

http://mytypo3page.tld/goto/Aefc-3E

Obviously you will need to tell you webserver how to handle these URLs.


.. _admin-speaking-url-configuration-apache:

Apache example configuration
----------------------------

When you are using mod_rewrite you can use this line in your .htaccess file or your webserver configuration:

::

    RewriteRule ^tinyurl/([a-zA-Z0-9]+(-[a-zA-Z0-9]+)?)$ /index.php?eID=tx_tinyurls&tx_tinyurls[key]=$1


.. _admin-speaking-url-configuration-lighttpd:

Lighttpd example configuration
------------------------------

When you use lighttpd you can use this configuration for rewriting tiny URLs:

::

    url.rewrite-if-not-file = (
        # rewrite goto urls to tinyurls extension
        "^/tinyurl/(.*)$" => typo3path + "index.php?eID=tx_tinyurls&tx_tinyurls[key]=$1",
    )
