#!/usr/bin/env bash

set -ev

phpenv config-rm xdebug.ini

# Rename our working directory, required for Extension upload to TER.
cd .. && mv typo3-extension-tinyurls tinyurls && cd tinyurls

if [ -n "$TRAVIS_TAG" ] && [ -n "$TYPO3_ORG_USERNAME" ] && [ -n "$TYPO3_ORG_PASSWORD" ]; then

    echo -e "Preparing upload of release ${TRAVIS_TAG} to TER\n"

    git reset --hard HEAD && git clean -fx

    TAG_MESSAGE=`git tag -n10 -l $TRAVIS_TAG | sed 's/^[0-9.]*[ ]*//g'`

    echo "Uploading release ${TRAVIS_TAG} to TER"

    .Build/bin/upload . "$TYPO3_ORG_USERNAME" "$TYPO3_ORG_PASSWORD" "$TAG_MESSAGE"
fi;
