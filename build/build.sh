#!/bin/bash

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

#     ___    __                __
#    /   |  / /_  ____  __  __/ /_
#   / /| | / __ \/ __ \/ / / / __/
#  / ___ |/ /_/ / /_/ / /_/ / /_
# /_/  |_/_.___/\____/\__,_/\__/

# Packages plugin to a .txz file in the archive folder of the repo.

#     ______                 __  _
#    / ____/_  ______  _____/ /_(_)___  ____  _____
#   / /_  / / / / __ \/ ___/ __/ / __ \/ __ \/ ___/
#  / __/ / /_/ / / / / /__/ /_/ / /_/ / / / (__  )
# /_/    \__,_/_/ /_/\___/\__/_/\____/_/ /_/____/

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


#    _____      __
#   / ___/___  / /___  ______
#   \__ \/ _ \/ __/ / / / __ \
#  ___/ /  __/ /_/ /_/ / /_/ /
# /____/\___/\__/\__,_/ .___/
#                    /_/

NAME="composerize"
VERSION="2022.08.01"

UPSTREAM_REPO="https://github.com/magicmark/composerize"
UPSTREAM_NAME="composerize"

FILE_NAME="$NAME-$VERSION.txz"

ARCHIVE_DIR=$(get_abs_path "./archive")
FILE=$(get_abs_path "$ARCHIVE_DIR/$FILE_NAME")
PACKAGE_DIR=$(get_abs_path "./source/$NAME")
PLUGIN_DIR=$(get_abs_path "$PACKAGE_DIR/usr/local/emhttp/plugins/$NAME")

UPSTREAM_BIN_FILE=$(get_abs_path "$PLUGIN_DIR/bin/$UPSTREAM_NAME")
PLUGIN_FILE=$(get_abs_path "./plugin/$NAME.plg")

#    ________
#   / ____/ /__  ____ _____  __  ______
#  / /   / / _ \/ __ `/ __ \/ / / / __ \
# / /___/ /  __/ /_/ / / / / /_/ / /_/ /
# \____/_/\___/\__,_/_/ /_/\__,_/ .___/
#                             /_/

echo "Cleaning up..."

rm "$UPSTREAM_BIN_FILE";
rm "$FILE"

#     ____        _ __    __       __
#    / __ )__  __(_) /___/ /  ____/ /__  ____  _____
#   / __  / / / / / / __  /  / __  / _ \/ __ \/ ___/
#  / /_/ / /_/ / / / /_/ /  / /_/ /  __/ /_/ (__  )
# /_____/\__,_/_/_/\__,_/   \__,_/\___/ .___/____/
#                                    /_/

echo "Building binary deps from git..."

set -e

pushd "$(mktemp -d)"
  git clone $UPSTREAM_REPO

  pushd "$UPSTREAM_NAME/packages/composerize"
    make build
    pkg -t node16-linuxstatic-x64 package.json
    cp -rv "$UPSTREAM_NAME" "$UPSTREAM_BIN_FILE"
  popd
popd

if [ -f "$UPSTREAM_BIN_FILE" ]; then
    echo "Dependencies built successfully. Continuing.."
else
    echo "ERROR: Failed to build composerize js."
    exit 1
fi



#     ____        _ __    __                    __
#    / __ )__  __(_) /___/ /  ____  ____ ______/ /______ _____ ____
#   / __  / / / / / / __  /  / __ \/ __ `/ ___/ //_/ __ `/ __ `/ _ \
#  / /_/ / /_/ / / / /_/ /  / /_/ / /_/ / /__/ ,< / /_/ / /_/ /  __/
# /_____/\__,_/_/_/\__,_/  / .___/\__,_/\___/_/|_|\__,_/\__, /\___/
#                         /_/                          /____/

echo "Building package for unraid..."

set -e

pushd "$PACKAGE_DIR"

echo "Setting file permissions..."
find usr/ -type f -exec dos2unix {} \;
chmod -R 755 usr/

echo "Creating archive..."
tar -cJf "$FILE" --owner=0 --group=0 usr/

popd

#    __  __          __      __          _       ____
#   / / / /___  ____/ /___ _/ /____     (_)___  / __/___
#  / / / / __ \/ __  / __ `/ __/ _ \   / / __ \/ /_/ __ \
# / /_/ / /_/ / /_/ / /_/ / /_/  __/  / / / / / __/ /_/ /
# \____/ .___/\__,_/\__,_/\__/\___/  /_/_/ /_/_/  \____/
#     /_/

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

echo "Done."
