#!/bin/bash
# Upload wordpress-org/assets PNGs to the WordPress.org plugin SVN /assets directory.
#
# Prerequisites:
#   - Subversion CLI (svn)
#   - WordPress.org plugin approved and SVN access granted
#   - Run scripts/generate-wporg-assets.py first if assets are missing
#
# Usage:
#   bash scripts/svn-upload-assets.sh [checkout-dir]
#
# Example:
#   bash scripts/svn-upload-assets.sh /tmp/privaro-cookie-consent-banner-svn

set -euo pipefail

PLUGIN_SLUG="privaro-cookie-consent-banner"
SRC_DIR="$(cd "$(dirname "$0")/.." && pwd)/wordpress-org/assets"
CHECKOUT_DIR="${1:-/tmp/${PLUGIN_SLUG}-svn}"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"

required_files=(
	icon-128x128.png
	icon-256x256.png
	banner-772x250.png
	banner-1544x500.png
	screenshot-1.png
	screenshot-2.png
	screenshot-3.png
	screenshot-4.png
)

echo "Checking source assets in ${SRC_DIR}..."
for file in "${required_files[@]}"; do
	if [[ ! -f "${SRC_DIR}/${file}" ]]; then
		echo "Missing ${SRC_DIR}/${file}"
		echo "Run: python3 scripts/generate-wporg-assets.py (see scripts/requirements-assets.txt)"
		exit 1
	fi
done

if [[ ! -d "${CHECKOUT_DIR}/.svn" ]]; then
	echo "Checking out ${SVN_URL} ..."
	svn co "${SVN_URL}" "${CHECKOUT_DIR}"
fi

mkdir -p "${CHECKOUT_DIR}/assets"
cp "${SRC_DIR}/"*.png "${CHECKOUT_DIR}/assets/"

cd "${CHECKOUT_DIR}/assets"
svn add --force *.png 2>/dev/null || true
svn status

echo
echo "Review the status above, then commit from ${CHECKOUT_DIR}/assets:"
echo "  svn ci -m \"Update plugin directory assets for ${PLUGIN_SLUG}\""
echo
echo "Assets are live on wordpress.org within a few minutes after commit."
