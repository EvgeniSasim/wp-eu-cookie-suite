# Contributing to Privaro Cookie Consent Banner

Thank you for your interest in contributing. This project is open source (GPL-2.0-or-later) and welcomes bug reports, documentation improvements, and code changes.

## Ways to contribute

- **Bug reports** — [open an issue](https://github.com/EvgeniSasim/wp-eu-cookie-suite/issues/new/choose) with steps to reproduce, expected vs actual behaviour, WordPress/PHP versions, and whether multisite is involved.
- **Feature ideas** — use the feature request template; explain the GDPR/compliance use case, not only the UI change.
- **Pull requests** — fork, branch from `main`, keep changes focused, and describe testing performed.
- **Translations** — text domain is `privaro-cookie-consent-banner`; submit `.po` files or GlotPress contributions on WordPress.org when the plugin is listed.

## Development setup

```bash
git clone https://github.com/EvgeniSasim/wp-eu-cookie-suite.git
cd wp-eu-cookie-suite
composer install
```

Optional frontend rebuild (bundled assets are already committed):

```bash
cd privaro-cookie-consent-banner
npm install && npm run build
```

### WordPress test suite

```bash
bin/install-wp-tests.sh wordpress_test root '' localhost latest
vendor/bin/phpunit
```

### Coding standards

```bash
vendor/bin/phpcs
vendor/bin/phpcbf   # auto-fix where possible
```

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/). Plugin code lives in `privaro-cookie-consent-banner/`.

For bootstrapping **new** open-source repos (CI, branch protection, WordPress.org), use the Cursor skill at [`.cursor/skills/oss-repo-setup/SKILL.md`](.cursor/skills/oss-repo-setup/SKILL.md).

## Pull request checklist

- [ ] `vendor/bin/phpcs` passes (CI runs on PHP 8.1 and 8.2).
- [ ] `vendor/bin/phpunit` passes when behaviour changes.
- [ ] User-facing strings use `privaro-cookie-consent-banner` text domain and are escaped on output.
- [ ] Admin forms use nonces; input is sanitized.
- [ ] `readme.txt` **Stable tag** matches plugin header `Version:` when releasing.
- [ ] `CHANGELOG.md` updated for user-visible changes.
- [ ] No secrets, credentials, or site-specific URLs in commits.

## Release process (maintainers)

1. Bump version in `privaro-cookie-consent-banner.php`, `includes/Plugin.php`, `readme.txt`, `CHANGELOG.md`.
2. `bash scripts/build-release.sh`
3. Tag: `git tag v1.x.x && git push origin v1.x.x` (triggers GitHub Release with zip).
4. WordPress.org SVN: `bash scripts/svn-publish-release.sh 1.x.x` (after approval).

## Code of conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md). Please be respectful and constructive.

## Security

Do not open public issues for security vulnerabilities. See [SECURITY.md](SECURITY.md).
