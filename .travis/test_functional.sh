#!/usr/bin/env bash

set -ev

echo "Funning functional tests..."

phpenv config-rm xdebug.ini

composer require --dev ${NIMUT_TESTING_FRAMEWORK}  --prefer-dist

mkdir -p ".Build/Web/typo3temp/var/tests"

export typo3DatabaseName="typo3"
export typo3DatabaseHost="localhost"
export typo3DatabaseUsername="root"
export typo3DatabasePassword=""

find . -wholename '*Tests/Functional/*Test.php' ! -path "./.Build/*" | \\
    parallel --gnu 'echo; echo "Running functional test suite {}"; .Build/bin/phpunit --colors -c .Build/vendor/nimut/testing-framework/res/Configuration/FunctionalTests.xml {}'
