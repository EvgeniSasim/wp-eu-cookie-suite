#!/usr/bin/env bash
# Upload an updated plugin ZIP to an in-progress WordPress.org review.
#
# Usage:
#   WPORG_USER=evgenij347 WPORG_PASS='...' bash scripts/wporg-upload-update.sh [zip-path] [comment]
#
# Defaults:
#   zip-path -> build/privaro-cookie-consent-banner.zip
#   comment  -> brief release note for reviewers

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ZIP_PATH="${1:-${REPO_ROOT}/build/privaro-cookie-consent-banner.zip}"
COMMENT="${2:-Privaro Cookie Consent Banner 1.3.2 — WordPress.org review fixes (blocking patterns, escaping, output buffer, legacy plugin notice). Request slug: privaro-cookie-consent-banner.}"

if [[ -z "${WPORG_USER:-}" || -z "${WPORG_PASS:-}" ]]; then
	echo "Set WPORG_USER and WPORG_PASS environment variables." >&2
	exit 2
fi

if [[ ! -f "${ZIP_PATH}" ]]; then
	echo "ZIP not found: ${ZIP_PATH}" >&2
	exit 2
fi

COOKIE_JAR="$(mktemp)"
trap 'rm -f "${COOKIE_JAR}"' EXIT

curl -sS -c "${COOKIE_JAR}" -b "${COOKIE_JAR}" 'https://login.wordpress.org/wp-login.php' -o /dev/null

curl -sS -c "${COOKIE_JAR}" -b "${COOKIE_JAR}" -L -X POST 'https://login.wordpress.org/wp-login.php' \
	-H 'Cookie: wordpress_test_cookie=WP%20Cookie%20check' \
	--data-urlencode "log=${WPORG_USER}" \
	--data-urlencode "pwd=${WPORG_PASS}" \
	--data-urlencode 'rememberme=forever' \
	--data-urlencode 'wp-submit=Log In' \
	--data-urlencode 'redirect_to=https://wordpress.org/plugins/developers/add/' \
	--data-urlencode 'testcookie=1' \
	-o /tmp/wporg-add.html

if ! grep -qi 'Howdy' /tmp/wporg-add.html; then
	echo "Login failed. Check WPORG_USER / WPORG_PASS." >&2
	exit 1
fi

NONCE="$(python3 - <<'PY'
import re
html=open('/tmp/wporg-add.html').read()
m=re.search(r'name="_wpnonce"\s+value="([^"]+)"', html)
print(m.group(1) if m else '')
PY
)"

PLUGIN_ID="$(python3 - <<'PY'
import re
html=open('/tmp/wporg-add.html').read()
m=re.search(r'name="plugin_id"\s+value="(\d+)"', html)
print(m.group(1) if m else '')
PY
)"

if [[ -z "${NONCE}" || -z "${PLUGIN_ID}" ]]; then
	echo "Could not find upload form (nonce/plugin_id). Is a plugin already in review?" >&2
	python3 - <<'PY'
import re
html=open('/tmp/wporg-add.html').read()
for pat in ['plugin-submission', 'upload-additional', 'already submitted', 'Being Reviewed']:
	if pat.lower() in html.lower():
		print('found:', pat)
PY
	exit 1
fi

echo "Uploading ${ZIP_PATH} to plugin_id=${PLUGIN_ID} ..."

curl -sS -b "${COOKIE_JAR}" -L -X POST 'https://wordpress.org/plugins/developers/add/' \
	-F "_wpnonce=${NONCE}" \
	-F "_wp_http_referer=/plugins/developers/add/" \
	-F "action=upload-additional" \
	-F "plugin_id=${PLUGIN_ID}" \
	-F "comment=${COMMENT}" \
	-F "zip_file=@${ZIP_PATH};type=application/zip" \
	-o /tmp/wporg-upload-result.html

python3 - <<'PY'
import re
html=open('/tmp/wporg-upload-result.html').read()
title=re.search(r'<title>([^<]+)</title>', html)
print('Title:', title.group(1) if title else '?')
for m in re.finditer(r'class="notice[^"]*"[^>]*>(.*?)</div>', html, re.S|re.I):
	text=re.sub('<[^>]+>',' ',m.group(1))
	text=' '.join(text.split())
	if text:
		print('NOTICE:', text[:500])
if 'plugin-submission-file' in html:
	print('OK: submission file list updated on page.')
if 'login' in html.lower() and 'wp-login' in html.lower():
	print('WARN: response looks like login redirect')
PY
