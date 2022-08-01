#!/bin/bash

# Packages plugin to a .txz file in the archive folder of the repo.
NAME="composerize"
VERSION="2022.08.01"

ARCHIVE_DIR="archive"
FILE_NAME="$NAME-$VERSION.txz"
FILE="$(cd "$(dirname "$ARCHIVE_DIR/$FILE_NAME")"; pwd)/$(basename "$ARCHIVE_DIR/$FILE_NAME")"
PACKAGE_DIR="source/$NAME"
PLUGIN_FILE="plugin/composerize.plg"

rm "$FILE"
set -e

# Updates an entity in the plugin file
function change_entity {
    local OLD_LINE_PATTERN=$1; shift
    local NEW_LINE=$1;

    OLD_LINE_PATTERN="<!ENTITY ${OLD_LINE_PATTERN}"

    local NEW=$(echo "${NEW_LINE}" | sed 's/\//\\\//g')

    NEW="${OLD_LINE_PATTERN} \"${NEW}\">"

    # FIX: No space after the option i.
    sed -i.bak '/'"${OLD_LINE_PATTERN}"'/s/.*/'"${NEW}"'/' "${PLUGIN_FILE}"
    mv "${PLUGIN_FILE}.bak" /tmp/
}

# Build package
pushd $PACKAGE_DIR

find usr/ -type f -exec dos2unix {} \;
chmod -R 755 usr/
tar -cJf "$FILE" --owner=0 --group=0 usr/

popd

# Update version and md5hash in plugin file
if [ -f "$FILE" ]; then
  hash=$(md5sum "$FILE" | cut -f 1 -d " ")
  echo "Packaged successfully: ${hash}"

  change_entity "md5" "${hash}"
  change_entity "version" "${VERSION}"
else
  echo "Failed to build package!"
fi

