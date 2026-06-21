# WordPress.org Assets

Marketing assets for the [Privaro Cookie Consent Banner](https://wordpress.org/plugins/privaro-cookie-consent-banner/) plugin directory listing.

## Files

| File | Purpose |
|------|---------|
| `icon-128x128.png` | Plugin icon (1x) |
| `icon-256x256.png` | Plugin icon (2x / retina) |
| `banner-772x250.png` | Plugin header banner |
| `banner-1544x500.png` | Plugin header banner (2x) |
| `screenshot-1.png` … `screenshot-4.png` | Readme screenshots (1280×720) |

Screenshots 1–4 match the **Screenshots** section in `privaro-cookie-consent-banner/readme.txt`. Current PNGs are **mock UI captures** for listing setup; replace with real admin/frontend screenshots when convenient.

## Regenerate assets

```bash
python3 -m venv .venv-assets
source .venv-assets/bin/activate
pip install -r scripts/requirements-assets.txt
python3 scripts/generate-wporg-assets.py
deactivate
```

Design: EU blue `#003399`, shield motif, Privaro branding.

## Upload to WordPress.org SVN

After plugin approval and SVN access:

```bash
bash scripts/svn-upload-assets.sh
```

Or publish a full release (trunk + tag + assets hint):

```bash
bash scripts/svn-publish-release.sh 1.3.1
bash scripts/svn-upload-assets.sh /tmp/privaro-cookie-consent-banner-svn
```

Manual steps:

1. `svn co https://plugins.svn.wordpress.org/privaro-cookie-consent-banner/ privaro-svn`
2. Copy `*.png` from this folder to `privaro-svn/assets/`
3. `cd privaro-svn/assets && svn add --force *.png && svn ci -m "Update plugin assets"`

Assets appear on wordpress.org within a few minutes after commit.

See also: `docs/wordpress-org-submission.md`
