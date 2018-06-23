#!/usr/bin/env bash

set -ev

echo "Running unit tests";

if [ ${TYPO3_VERSION} = "typo3/minimal=dev-master" ]; then
    config minimum-stability dev && composer require ${TYPO3_VERSION} --prefer-stable --prefer-dist
else
    composer require ${TYPO3_VERSION}
fi


if [[ "${UPLOAD_CODE_COVERAGE}" == "yes" ]]; then
    .Build/bin/phpunit --coverage-clover .Build/Logs/clover.xml --whitelist Classes Tests/Unit/
else
    phpenv config-rm xdebug.ini
    .Build/bin/phpunit Tests/Unit/
fi

if [[ "${UPLOAD_CODE_COVERAGE}" == "yes" ]]; then
    composer require --dev codeclimate/php-test-reporter:dev-master;
    .Build/bin/test-reporter --coverage-report .Build/Logs/clover.xml;
    composer remove codeclimate/php-test-reporter --dev
fi
