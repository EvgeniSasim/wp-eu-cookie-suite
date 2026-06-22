#!/usr/bin/env bash
# Protect GitHub main branch with CI checks and PR review requirements.
#
# Usage:
#   protect-main.sh owner/repo [check-context ...]
#
# If no check contexts are passed, reads successful check runs from the
# latest commit on the default branch.
#
# Requires: gh (authenticated), jq

set -euo pipefail

REPO="${1:?Usage: protect-main.sh owner/repo [check-context ...]}"
shift || true

if ! command -v gh >/dev/null 2>&1; then
	echo "gh CLI is required." >&2
	exit 2
fi

if ! command -v jq >/dev/null 2>&1; then
	echo "jq is required." >&2
	exit 2
fi

DEFAULT_BRANCH="$(gh api "repos/${REPO}" --jq .default_branch)"
HEAD_SHA="$(gh api "repos/${REPO}/git/ref/heads/${DEFAULT_BRANCH}" --jq .object.sha)"

if [[ $# -eq 0 ]]; then
	CONTEXTS=()
	while IFS= read -r line; do
		[[ -n "$line" ]] && CONTEXTS+=("$line")
	done < <(
		gh api "repos/${REPO}/commits/${HEAD_SHA}/check-runs" \
			--jq '.check_runs[] | select(.conclusion=="success") | .name' | sort -u
	)
else
	CONTEXTS=("$@")
fi

if [[ ${#CONTEXTS[@]} -eq 0 ]]; then
	echo "No successful CI checks found on ${DEFAULT_BRANCH} (${HEAD_SHA:0:7})." >&2
	echo "Run CI on main first, or pass check names explicitly." >&2
	exit 1
fi

CONTEXTS_JSON="$(printf '%s\n' "${CONTEXTS[@]}" | jq -R . | jq -s .)"

PAYLOAD="$(jq -n \
	--argjson contexts "${CONTEXTS_JSON}" \
	'{
		required_status_checks: { strict: true, contexts: $contexts },
		enforce_admins: true,
		required_pull_request_reviews: {
			dismiss_stale_reviews: true,
			require_code_owner_reviews: false,
			required_approving_review_count: 1
		},
		restrictions: null,
		allow_force_pushes: false,
		allow_deletions: false,
		required_conversation_resolution: true
	}')"

echo "Protecting ${REPO}:${DEFAULT_BRANCH}"
echo "Required checks: ${CONTEXTS[*]}"

gh api \
	--method PUT \
	"repos/${REPO}/branches/${DEFAULT_BRANCH}/protection" \
	--input - <<<"${PAYLOAD}"

echo "Done."
