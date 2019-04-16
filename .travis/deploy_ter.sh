#!/usr/bin/env bash

set -ev

phpenv config-rm xdebug.ini

echo "Cleanup Git repository..."
git reset --hard HEAD && git clean -fx

if [[ -z "$TRAVIS_TAG" ]]; then
    echo "No Travis tag is available. Upload only runs for new tags."
    exit 0
fi

if [[ -z "$TYPO3_ORG_USERNAME" ]]; then
    echo "The $TYPO3_ORG_USERNAME env var is not set."
    exit 1
fi

if [[ -z "$TYPO3_ORG_PASSWORD" ]]; then
    echo "The $TYPO3_ORG_PASSWORD env var is not set."
    exit 1
fi

TAG_MESSAGE=`git tag -n10 -l ${TRAVIS_TAG} | sed 's/^v[0-9.]*[ ]*//g'`

if [[ -z "$TAG_MESSAGE" ]]; then
    echo "The tag message could not be detected or was empty."
    exit 1
fi

echo "Extracted tag message: $TAG_MESSAGE"


echo "Renaming repository folder to match extension key..."
cd ..
mv typo3-extension-tinyurls tinyurls

echo "Installing TYPO3 repository client..."

composer create-project --no-dev namelesscoder/typo3-repository-client typo3-repository-client

cd tinyurls

echo "Setting version to ${TRAVIS_TAG#"v"}"
../typo3-repository-client/bin/setversion ${TRAVIS_TAG#"v"}

echo "Uploading release ${TRAVIS_TAG} to TER"
../typo3-repository-client/bin/upload . "$TYPO3_ORG_USERNAME" "$TYPO3_ORG_PASSWORD" "$TAG_MESSAGE"
