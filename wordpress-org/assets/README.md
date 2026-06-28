# WordPress.org Assets

Marketing assets for the [Privaro Cookie Consent Banner](https://wordpress.org/plugins/privaro-cookie-consent-banner/) plugin directory listing and the GitHub repository README.

## Files

| File | Purpose |
|------|---------|
| `icon-128x128.png` | Plugin icon (1×) |
| `icon-256x256.png` | Plugin icon (2× / retina) |
| `banner-772x250.png` | Plugin header banner |
| `banner-1544x500.png` | Plugin header banner (2×) |
| `screenshot-1.png` … `screenshot-4.png` | Readme screenshots (1280×720) |
| `social-preview.png` | Source for GitHub README hero (also copied to `docs/assets/`) |

Mirrored copies for the GitHub README live in [`docs/assets/`](../docs/assets/) (`logo.svg`, `hero-banner.png`, screenshots).

## Regenerate assets

```bash
python3 -m venv .venv-assets
source .venv-assets/bin/activate
pip install -r scripts/requirements-assets.txt
python3 scripts/generate-wporg-assets.py
deactivate
```

Design: EU blue palette, shield + cookie motif, product-style admin mocks.

## Upload to WordPress.org SVN

```bash
bash scripts/svn-upload-assets.sh
# then commit from the checkout assets/ directory
```

Or with an existing SVN checkout:

```bash
bash scripts/svn-upload-assets.sh /tmp/privaro-cookie-consent-banner-svn
cd /tmp/privaro-cookie-consent-banner-svn/assets
svn ci -m "Update plugin directory assets"
```

Assets appear on wordpress.org within a few minutes after commit.

See also: `docs/wordpress-org-submission.md`
