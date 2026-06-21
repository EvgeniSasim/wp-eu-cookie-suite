#!/usr/bin/env bash
# Best-effort post-deploy cookie consent audit for a public homepage.
#
# Usage:
#   bash scripts/verify-consent-audit.sh https://staging.example.com
#
# Exit 0 when all automated checks pass; exit 1 otherwise.

set -euo pipefail

URL="${1:-}"
if [[ -z "${URL}" ]]; then
	echo "Usage: $0 <homepage-url>"
	exit 2
fi

PASS=0
FAIL=0

pass() {
	echo "[PASS] $*"
	PASS=$((PASS + 1))
}

fail() {
	echo "[FAIL] $*"
	FAIL=$((FAIL + 1))
}

warn() {
	echo "[WARN] $*"
}

echo "Auditing: ${URL}"
echo

HTML="$(curl -fsSL -A "PrivaroConsentAudit/1.0" "${URL}" 2>/dev/null || true)"
if [[ -z "${HTML}" ]]; then
	fail "Could not fetch homepage HTML"
	echo
	echo "Summary: ${PASS} passed, ${FAIL} failed"
	exit 1
fi

if echo "${HTML}" | grep -q 'cc\.run\s*('; then
	pass "CookieConsent v3 bootstrap (cc.run) found in HTML"
elif echo "${HTML}" | grep -qi 'cookieconsent\|wpeu-cs\|privaro'; then
	warn "Consent markup present but cc.run() not detected — may be loaded async"
else
	fail "No cookie consent markup detected in initial HTML"
fi

if echo "${HTML}" | grep -q 'wp-consent-api\|wp_has_consent\|wp_set_consent'; then
	pass "WP Consent API integration markers present"
else
	warn "WP Consent API markers not found in HTML (polyfill may still work)"
fi

HEADERS="$(curl -fsSI -A "PrivaroConsentAudit/1.0" "${URL}" 2>/dev/null || true)"
if echo "${HEADERS}" | grep -qi 'set-cookie:.*_ga'; then
	fail "Set-Cookie _ga on first response — analytics may load before consent"
else
	pass "No _ga Set-Cookie on initial response (best-effort)"
fi

if echo "${HEADERS}" | grep -qi 'set-cookie:.*_fbp'; then
	fail "Set-Cookie _fbp on first response — marketing pixel may load before consent"
else
	pass "No _fbp Set-Cookie on initial response (best-effort)"
fi

echo
echo "Manual checks (complete in browser):"
echo "  - Reject all → no new marketing/statistics cookies"
echo "  - Accept all → expected cookies appear"
echo "  - Revoke → banner shown again"
echo "  - Only privaro-cookie-consent-banner active in wp-admin"
echo
echo "Summary: ${PASS} passed, ${FAIL} failed"

if [[ "${FAIL}" -gt 0 ]]; then
	exit 1
fi

exit 0
