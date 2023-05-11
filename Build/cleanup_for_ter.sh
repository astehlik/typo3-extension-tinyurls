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
rm -Rf Build
rm -f .gitignore
rm -Rf Tests
rm -Rf typo3temp
rm ready_for_release.txt
