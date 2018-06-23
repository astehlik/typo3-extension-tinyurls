#!/usr/bin/env bash

set -ev

echo "Running php lint"

phpenv config-rm xdebug.ini

find . -name \*.php ! -path "./.Build/*" | parallel --gnu php -d display_errors=stderr -l {} > /dev/null
