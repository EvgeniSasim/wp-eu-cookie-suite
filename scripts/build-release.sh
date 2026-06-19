#!/bin/bash
# scripts/build-release.sh

set -e

PLUGIN_SLUG="eu-cookie-consent-suite"
PLUGIN_DIR="eu-cookie-consent-suite"
BUILD_DIR="build"

echo "Verifying versions..."

VERSION_PHP=$(grep "Version:" $PLUGIN_DIR/$PLUGIN_SLUG.php | awk '{print $NF}')
VERSION_README=$(grep "Stable tag:" $PLUGIN_DIR/readme.txt | awk '{print $NF}')

echo "Plugin version (PHP): $VERSION_PHP"
echo "Stable tag (readme): $VERSION_README"

if [ "$VERSION_PHP" != "$VERSION_README" ]; then
    echo "ERROR: Version mismatch between PHP header and readme.txt"
    exit 1
fi

# Ensure build directory exists
mkdir -p $BUILD_DIR
rm -rf $BUILD_DIR/$PLUGIN_SLUG
rm -f $BUILD_DIR/$PLUGIN_SLUG.zip

echo "Preparing build directory..."
cp -r $PLUGIN_DIR $BUILD_DIR/$PLUGIN_SLUG

# Cleanup build directory using .distignore
echo "Cleaning up using .distignore..."
while read line; do
    if [ -n "$line" ]; then
        rm -rf $BUILD_DIR/$PLUGIN_SLUG/$line
    fi
done < $PLUGIN_DIR/.distignore

# Zip the plugin
echo "Creating zip archive..."
cd $BUILD_DIR
zip -r $PLUGIN_SLUG.zip $PLUGIN_SLUG
cd ..

echo "Build successful: $BUILD_DIR/$PLUGIN_SLUG.zip"
