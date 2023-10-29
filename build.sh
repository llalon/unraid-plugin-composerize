#!/bin/bash

# Detect MAC OSX
if [ "$(uname)" == "Darwin" ]; then
  PREFIX="g"
else
  PREFIX=""
fi

set -e

USAGE="Usage: $(basename "$0") <plugin> [branch] [version]"
ARCHIVE_DIR="./archive"
SOURCE_DIR="./source"

PLUGIN_FILE="$1"
BRANCH="$2"
VERSION="$3"

# Validate arguments
if [ "$#" -lt 1 ] || [ "$#" -gt 3 ]; then
    echo "$USAGE"
    exit 1
fi

if [[ $PLUGIN_FILE != *.plg ]]; then
    echo "$USAGE"
    exit 1
fi

if [ -z "$BRANCH" ]; then
  BRANCH="main"
fi

if [ -z "$VERSION" ]; then
  VERSION=$(date +%Y%m%d)
fi

# Extract name from plugin file - source dir must match
NAME=$("${PREFIX}sed" -n 's/<!ENTITY[[:space:]]\+name[[:space:]]\+"\(.*\)">/\1/p' "$PLUGIN_FILE")
if [ -z "$NAME" ]; then
    echo "Error: Pattern not found in the file."
    exit 1
fi

FILE_NAME="$NAME-$VERSION.txz"
PACKAGE_DIR="$SOURCE_DIR/$NAME"

# Validate source
if [ -d "$PACKAGE_DIR" ]; then
    if [ -z "$(ls -A "$PACKAGE_DIR")" ]; then
        echo "Folder exists but is empty."
        exit 1
    fi
else
    echo "Folder does not exist."
    exit 1
fi

echo "================================================"
echo "           Building UnRaid plugin package"
echo "================================================"
echo "Plugin: ${PLUGIN_FILE}"
echo "Name: ${NAME}"
echo "Source: ${PACKAGE_DIR}"
echo "Archive: ${ARCHIVE_DIR}"
echo "================================================"

mkdir -p "$ARCHIVE_DIR"
FILE="$(realpath ${ARCHIVE_DIR})/$FILE_NAME"


pushd "$PACKAGE_DIR"
  echo "Setting file permissions..."
  find usr -type f -exec dos2unix {} \;
  chmod -R 755 usr/

  echo "Creating archive..."
  "${PREFIX}tar" -cJf "$FILE" --owner=0 --group=0 usr/
popd

echo "Verifying package"
if [ -f "$FILE" ]; then
  hash=$(md5sum "$FILE" | cut -f 1 -d " ")

  if [ -z "$hash" ]; then
    echo "Could not verify archive"
    exit 1
  fi

  echo "Packaged successfully: ${hash}"

  echo "Updating plugin info..."
  "${PREFIX}sed" -i.bak '/'"<!ENTITY md5"'/s/.*/'"<!ENTITY md5 \"${hash}\">"'/' "${PLUGIN_FILE}"
  "${PREFIX}sed" -i.bak '/'"<!ENTITY version"'/s/.*/'"<!ENTITY version \"${VERSION}\">"'/' "${PLUGIN_FILE}"
  "${PREFIX}sed" -i.bak '/'"<!ENTITY branch"'/s/.*/'"<!ENTITY branch \"${BRANCH}\">"'/' "${PLUGIN_FILE}"
else
  echo "Failed to build package!"
fi

echo "Done."