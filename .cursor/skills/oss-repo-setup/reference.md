# OSS Repo Setup — Reference

## Branch protection payload

Full JSON used by `protect-main.sh`:

```json
{
  "required_status_checks": {
    "strict": true,
    "contexts": ["test (8.1)", "test (8.2)"]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": {
    "dismiss_stale_reviews": true,
    "require_code_owner_reviews": false,
    "required_approving_review_count": 1
  },
  "restrictions": null,
  "allow_force_pushes": false,
  "allow_deletions": false,
  "required_conversation_resolution": true
}
```

To relax for solo dev (no review gate):

```json
"required_pull_request_reviews": null
```

---

## File templates

### Minimal SECURITY.md

```markdown
# Security Policy

## Reporting a Vulnerability

Please **do not** open a public GitHub issue for security bugs.

Email [your-email] with:
- Description and impact
- Steps to reproduce
- Affected versions

We aim to respond within 7 days.
```

### dependabot.yml skeleton

```yaml
version: 2
updates:
  - package-ecosystem: composer
    directory: "/"
    schedule:
      interval: weekly
    open-pull-requests-limit: 5

  - package-ecosystem: github-actions
    directory: "/"
    schedule:
      interval: weekly
    open-pull-requests-limit: 5
```

### WP plugin .distignore (common entries)

```
.git
.github
node_modules
tests
docs
prompts
*.md
!readme.txt
composer.json
composer.lock
package.json
package-lock.json
phpunit.xml.dist
bin
```

---

## WordPress.org plugin directory

### readme.txt required headers

```
=== Plugin Name ===
Contributors: wporg-username
Tags: ...
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

`Stable tag` must equal PHP `Version` header.

### Pre-submit checks

- [ ] No `WP` prefix in plugin display name
- [ ] `Contributors:` matches wordpress.org username
- [ ] `vendor/bin/phpcs` clean (WPCS)
- [ ] Plugin Check plugin locally if available
- [ ] Zip folder name matches assigned/reserved slug
- [ ] `== External services ==` section if blocking third-party URLs
- [ ] `uninstall.php` removes all plugin data
- [ ] No secrets, no trialware, no arbitrary admin JS/CSS fields

### Review in progress

If a submission already exists, upload updates via **Add your plugin** page (`action=upload-additional`), then **reply to plugins@wordpress.org** email. New slug must be requested explicitly in that reply.

Use `scripts/wporg-upload-update.sh` in this repo (requires `WPORG_USER` / `WPORG_PASS` env vars).

### SVN layout (post-approval)

```
/assets/          # icon-256x256.png, banner-772x250.png, screenshot-*.png
/trunk/           # plugin source
/tags/1.0.0/      # copy of trunk at release
```

---

## gh snippets

List repo topics:

```bash
gh api repos/owner/repo --jq .topics
```

Add GitHub secret (for SVN deploy workflows):

```bash
gh secret set SVN_USERNAME --repo owner/repo
gh secret set SVN_PASSWORD --repo owner/repo
```

Close superseded Dependabot PR:

```bash
gh pr close 33 --comment "Superseded by abc1234 on main."
```
