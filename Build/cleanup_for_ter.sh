#!/bin/bash

set -e

if [ ! -f ready_for_release.txt ]; then
  echo "The file ready_for_release.txt does not exists. Make sure you run this script in the right directory!"
  exit 1
fi

if [ -d ".git" ]; then
  git clean -fdX
fi

rm -Rf .git
rm -Rf .github
rm -Rf .phpstorm.meta.php
rm -Rf .Build
rm -Rf Build
rm -Rf Tests
rm -Rf typo3temp
rm -f .codeclimate.yml
rm -f .crowdin.yml
rm -f .editorconfig
rm -f .gitignore
rm ready_for_release.txt
