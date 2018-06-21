#!/usr/bin/env bash

set -ev

xdebug-enable;

echo "Generating coverage report";

.Build/bin/phpunit --coverage-clover .Build/Logs/clover.xml --whitelist Classes Tests/Unit/

xdebug-disable;
