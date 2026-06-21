#!/usr/bin/env bash
# Publish a tagged plugin release to WordPress.org SVN (trunk + tags/X.Y.Z).
#
# Usage:
#   bash scripts/svn-publish-release.sh 1.3.1 [checkout-dir]
#
# Requires: svn, build/privaro-cookie-consent-banner.zip or fresh build

set -euo pipefail

VERSION="${1:-}"
CHECKOUT_DIR="${2:-/tmp/privaro-cookie-consent-banner-svn}"
PLUGIN_SLUG="privaro-cookie-consent-banner"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"

if [[ -z "${VERSION}" ]]; then
	echo "Usage: $0 <version> [checkout-dir]"
	exit 2
fi

PHP_VERSION="$(grep 'Version:' "${REPO_ROOT}/${PLUGIN_SLUG}/${PLUGIN_SLUG}.php" | awk '{print $NF}')"
if [[ "${PHP_VERSION}" != "${VERSION}" ]]; then
	echo "ERROR: Plugin header version (${PHP_VERSION}) != requested tag (${VERSION})"
	exit 1
fi

bash "${REPO_ROOT}/scripts/build-release.sh"

if [[ ! -d "${CHECKOUT_DIR}/.svn" ]]; then
	svn co "${SVN_URL}" "${CHECKOUT_DIR}"
fi

rm -rf "${CHECKOUT_DIR}/trunk"
mkdir -p "${CHECKOUT_DIR}/trunk"
unzip -q "${REPO_ROOT}/build/${PLUGIN_SLUG}.zip" -d "${CHECKOUT_DIR}"
mv "${CHECKOUT_DIR}/${PLUGIN_SLUG}"/* "${CHECKOUT_DIR}/trunk/"
rmdir "${CHECKOUT_DIR}/${PLUGIN_SLUG}"

cd "${CHECKOUT_DIR}"
svn add --force trunk 2>/dev/null || true

if svn info "tags/${VERSION}" >/dev/null 2>&1; then
	echo "Tag tags/${VERSION} already exists in SVN checkout."
else
	svn cp trunk "tags/${VERSION}"
fi

svn status
echo
echo "Review status, then commit:"
echo "  cd ${CHECKOUT_DIR} && svn ci -m \"Release ${VERSION}\""
echo
echo "Upload marketing assets separately:"
echo "  bash scripts/svn-upload-assets.sh ${CHECKOUT_DIR}"
