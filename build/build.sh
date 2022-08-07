#!/bin/bash

# Packages plugin to a .txz file in the archive folder of the repo.

# MIT License
#
# Copyright (c) 2022 Liam Lalonde - 'llalon'
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.

function change_entity {
    # Changes an entity field in plugin/composerize.plg
    local o=$1; shift
    local l=$1;

    o="<!ENTITY ${o}"
    local n=$(echo "${l}" | sed 's/\//\\\//g')
    n="${o} \"${n}\">"

    sed -i.bak '/'"${o}"'/s/.*/'"${n}"'/' "${PLUGIN_FILE}"
    mv "${PLUGIN_FILE}.bak" /tmp/
}

function get_abs_path {
  echo "$(cd "$(dirname "$1/$2")"; pwd)/$(basename "$1/$2")"
}

set -e

NAME="composerize"
VERSION=$1

if [ -z "$VERSION" ]; then
  VERSION=$(date '+%Y.%m.%d')
fi

FILE_NAME="$NAME-$VERSION.txz"

ARCHIVE_DIR=$(get_abs_path "./archive")
FILE=$(get_abs_path "$ARCHIVE_DIR/$FILE_NAME")
PACKAGE_DIR=$(get_abs_path "./source/$NAME")
PLUGIN_FILE=$(get_abs_path "./plugin/$NAME.plg")

echo "Building version: ${VERSION}"

echo "Building package for unraid..."

pushd "$PACKAGE_DIR"
  echo "Setting file permissions..."
  find usr/ -type f -exec dos2unix {} \;
  chmod -R 755 usr/

  echo "Creating archive..."
  if [ "$(uname)" == "Darwin" ]; then
      gtar -cJf "$FILE" --owner=0 --group=0 usr/
  else
      tar -cJf "$FILE" --owner=0 --group=0 usr/
  fi
popd

echo "Updating unraid package info..."

set -e

if [ -f "$FILE" ]; then
  hash=$(md5sum "$FILE" | cut -f 1 -d " ")
  echo "Packaged successfully: ${hash}"

  change_entity "md5" "${hash}"
  change_entity "version" "${VERSION}"
else
  echo "Failed to build package!"
fi

cp -rv "$FILE" "${NAME}-latest.txz"

echo "Done."
