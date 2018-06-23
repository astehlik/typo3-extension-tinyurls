#!/usr/bin/env bash

set -ev

echo "Running phpcs"

phpenv config-rm xdebug.ini

composer create-project --no-dev squizlabs/php_codesniffer:^3.3 codesniffer

cd codesniffer

composer require --update-no-dev de-swebhosting/php-codestyle:dev-master

cd ..

./codesniffer/bin/phpcs --config-set installed_paths $PWD/codesniffer/vendor/de-swebhosting/php-codestyle/PhpCodeSniffer,Tests/CodeSniffer

./codesniffer/bin/phpcs --standard=PSRTinyurls Classes Configuration/TCA Tests/Unit Tests/Functional ext_emconf.php ext_localconf.php
