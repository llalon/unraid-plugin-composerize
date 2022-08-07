#!/bin/bash

# Clones from git and builds a statically linked binary for the composerize cli tool.

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

function get_abs_path {
  echo "$(cd "$(dirname "$1/$2")"; pwd)/$(basename "$1/$2")"
}

set -e

NAME="composerize"
UPSTREAM_NAME="composerize"
UPSTREAM_REPO="https://github.com/magicmark/composerize"

PACKAGE_DIR=$(get_abs_path "./source/$NAME")
PLUGIN_DIR=$(get_abs_path "$PACKAGE_DIR/usr/local/emhttp/plugins/$NAME")

UPSTREAM_BIN_FILE=$(get_abs_path "$PLUGIN_DIR/bin/$UPSTREAM_NAME")

echo "Building binary deps from git..."

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

echo "Done."