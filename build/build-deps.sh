#!/bin/bash

UPSTREAM_REPO="https://github.com/magicmark/composerize"
NAME=composerize

set -e

get_abs_filename() {
  echo "$(cd "$(dirname "$1")" && pwd)/$(basename "$1")"
}

pushd "$(mktemp -d)"
  git clone $UPSTREAM_REPO

  pushd "NAME"
    pkg -t node16-linuxstatic-x64 package.json
    bin_file_path=$(get_abs_filename "NAME")
  popd
popd

