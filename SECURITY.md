# Security Policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 1.3.x   | Yes       |
| < 1.3   | Best effort |

Security fixes are released for the current stable branch. Upgrade via WordPress admin or replace the plugin from the latest [GitHub Release](https://github.com/EvgeniSasim/wp-eu-cookie-suite/releases).

## Reporting a vulnerability

**Please do not report security issues in public GitHub issues.**

1. Use [GitHub Security Advisories](https://github.com/EvgeniSasim/wp-eu-cookie-suite/security/advisories/new) (preferred), or
2. Contact the maintainer via the email linked on their [WordPress.org profile](https://profiles.wordpress.org/evgenij347/).

Include:

- Description of the vulnerability and impact
- Steps to reproduce
- Affected plugin version
- WordPress / PHP version if relevant
- Proof of concept if available (no destructive tests on third-party sites)

We aim to acknowledge reports within **5 business days** and provide a fix or mitigation plan within **30 days** for confirmed issues.

## Scope

In scope:

- Privaro Cookie Consent Banner plugin code in `privaro-cookie-consent-banner/`
- Consent logging, admin actions, AJAX endpoints, import/export, multisite settings

Out of scope:

- Vulnerabilities in WordPress core, themes, or other plugins
- Server misconfiguration (file permissions, exposed `.env`)
- Social engineering or phishing

## Safe harbour

We appreciate responsible disclosure and will credit reporters in the release notes when agreed.
