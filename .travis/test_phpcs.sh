#!/usr/bin/env bash

set -ev

echo "Running phpcs"

phpenv config-rm xdebug.ini

.Build/bin/phpcs --config-set installed_paths $PWD/.Build/vendor/de-swebhosting/php-codestyle/PhpCodeSniffer,Tests/CodeSniffer

.Build/bin/phpcs --standard=PSRTinyurls Classes Configuration/TCA Tests/Unit Tests/Functional ext_emconf.php ext_localconf.php
