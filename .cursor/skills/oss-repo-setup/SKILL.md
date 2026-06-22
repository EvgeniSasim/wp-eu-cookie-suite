---
name: oss-repo-setup
description: >-
  Bootstrap and harden GitHub open-source repositories: metadata, LICENSE,
  community files, CI/release workflows, Dependabot, and main branch protection.
  Use when creating a new public repo, preparing a project for open source,
  configuring GitHub settings, branch protection, or OSS release hygiene.
---

# Open Source Repository Setup

Apply this skill when the user wants a new or existing GitHub repo prepared for public open source work. Prefer **defaults below** unless the user specifies otherwise.

## Defaults

| Setting | Default |
|---------|---------|
| License | `GPL-2.0-or-later` (WordPress plugins) or `MIT` (generic libs/tools) |
| Default branch | `main` |
| Merge strategy | Squash or merge commits; **no direct push to `main`** after protection |
| Branch protection | CI required, 1 PR approval, enforce for admins |
| Tooling | `gh` CLI for all GitHub API/settings |

Ask once if project type is unclear: **WordPress plugin**, **npm/library**, or **generic app**.

## Workflow checklist

Copy and track progress:

```
OSS repo setup:
- [ ] 1. Repo metadata (description, topics, homepage, visibility)
- [ ] 2. Community & legal files
- [ ] 3. CI workflow (+ local lint/test commands)
- [ ] 4. Release workflow (tags → GitHub Release)
- [ ] 5. Dependabot
- [ ] 6. PR/issue templates
- [ ] 7. Branch protection on `main`
- [ ] 8. Verify CI green on `main`
- [ ] 9. (WP plugin only) WordPress.org pack
```

Execute steps yourself with `gh` and file edits. Do not only list instructions.

---

## 1. Repository metadata

```bash
REPO="owner/repo-name"
gh repo edit "$REPO" \
  --description "Short searchable description" \
  --homepage "https://github.com/owner/repo-name" \
  --add-topic open-source \
  --add-topic wordpress-plugin   # omit if not WP
```

Public visibility:

```bash
gh repo edit "$REPO" --visibility public --accept-visibility-change-constraints
```

Rename repo slug if needed (do **before** WordPress.org submission):

```bash
gh repo rename new-slug --repo "$REPO"
```

---

## 2. Community & legal files

Minimum set for OSS:

| File | Purpose |
|------|---------|
| `LICENSE` | SPDX identifier in repo root; copy in plugin/package folder if nested |
| `README.md` | Install, features, badges, link to contributing |
| `CONTRIBUTING.md` | How to report bugs, PR flow, dev setup, coding standards |
| `SECURITY.md` | How to report vulnerabilities (no public issues for exploits) |
| `CODE_OF_CONDUCT.md` | Contributor Covenant 2.1 is fine |
| `CHANGELOG.md` | Keep a `[Unreleased]` section; tag sections match releases |

**WordPress plugin** additionally:

| File | Purpose |
|------|---------|
| `readme.txt` | WordPress.org format; `Stable tag` = PHP `Version` |
| `uninstall.php` | Drop options/tables |
| `.distignore` | Exclude dev files from release zip |
| `THIRD-PARTY.md` | Bundled libraries + licenses |

Never commit secrets (`.env`, API keys, deploy credentials). Add to `.gitignore`.

---

## 3. CI workflow

Create `.github/workflows/ci.yml`:

- Triggers: `push` + `pull_request` on `main`
- Matrix PHP/Node versions as needed
- Steps: checkout → install deps → lint → test
- Job names become **required status check** names (e.g. `test (8.1)`)

After first green run on `main`, record check names:

```bash
gh api "repos/$REPO/commits/$(gh api repos/$REPO --jq .default_branch | xargs -I{} gh api repos/$REPO/git/ref/heads/{} --jq .object.sha)/check-runs" \
  --jq '.check_runs[] | select(.conclusion=="success") | .name' | sort -u
```

Use these exact strings for branch protection.

---

## 4. Release workflow

Create `.github/workflows/release.yml`:

- Trigger: `push` tags `v*`
- Verify tag version matches package header + readme stable tag
- Build artifact (zip for WP plugins)
- `softprops/action-gh-release@v3` with `files:` and changelog excerpt

Tag convention: `v1.2.3` matching semver in code.

---

## 5. Dependabot

Create `.github/dependabot.yml` (version 2):

- `composer` weekly (PHP projects)
- `npm` monthly per package directory
- `github-actions` weekly

When Dependabot PRs fail CI for unrelated reasons (e.g. missing fix on `main`), fix `main` first, then close superseded PRs with a comment pointing to the fixing commit.

---

## 6. PR & issue templates

Under `.github/`:

- `pull_request_template.md` — summary, test plan, checklist
- `ISSUE_TEMPLATE/bug_report.yml` + `feature_request.yml`
- `ISSUE_TEMPLATE/config.yml` — disable blank issues if desired

---

## 7. Branch protection (required)

Run after CI has succeeded at least once on `main`:

```bash
bash .cursor/skills/oss-repo-setup/scripts/protect-main.sh owner/repo-name
```

Or pass check names explicitly:

```bash
bash .cursor/skills/oss-repo-setup/scripts/protect-main.sh owner/repo-name "test (8.1)" "test (8.2)"
```

Script defaults (used in this repo):

- Required status checks (strict)
- 1 approving review, dismiss stale reviews
- Require conversation resolution
- Block force push and branch deletion
- **enforce_admins: true** (admins cannot bypass)

If solo maintainer cannot self-approve: temporarily set `required_approving_review_count` to 0, or use a second GitHub account/bot — mention trade-off to user.

Verify:

```bash
gh api "repos/$REPO/branches/main/protection" --jq '{reviews: .required_pull_request_reviews, admins: .enforce_admins.enabled, checks: .required_status_checks.contexts}'
```

---

## 8. Post-setup verification

```bash
gh run list --repo "$REPO" --branch main --limit 3
gh api "repos/$REPO/branches/main/protection" 2>/dev/null || echo "NOT PROTECTED"
```

Confirm GitHub UI no longer shows “Your main branch isn't protected”.

---

## 9. WordPress.org (plugins only)

See [reference.md](reference.md#wordpressorg-plugin-directory).

High level:

1. `bash scripts/build-release.sh` → clean zip
2. Plugin Check locally before submit
3. Initial submit at https://wordpress.org/plugins/developers/add/
4. After approval: SVN trunk/tags/assets via `plugins.svn.wordpress.org/{slug}`

Slug/display name: distinctive, no `WP` prefix, avoid generic “Cookie Consent” alone. Request slug change in review email if renaming.

This repo also has `scripts/wporg-upload-update.sh` for uploading a new ZIP during an in-progress review.

---

## Commit style for setup work

Use focused commits, e.g.:

- `chore: add CI workflow and Dependabot`
- `chore: add OSS community files and GPL-2.0 license`
- `fix(ci): PHPCS array spacing`

Do not add `Co-authored-by: Cursor` lines.

---

## Additional resources

- Branch protection API details: [reference.md](reference.md#branch-protection-payload)
- File templates and `gh` snippets: [reference.md](reference.md#file-templates)
- WordPress.org checklist: [reference.md](reference.md#wordpressorg-plugin-directory)
