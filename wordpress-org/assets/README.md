# WordPress.org Assets

This directory contains the marketing assets for the WordPress.org Plugin Directory.

## Contents
- `icon-128x128.png` / `icon-256x256.png`: Plugin icons.
- `banner-772x250.png` / `banner-1544x500.png`: Plugin banners.
- `screenshot-1..4.png`: Screenshots (mock placeholders).

## SVN Upload Instructions
To update assets in the WordPress.org Plugin Directory:

1. Checkout the assets directory of your SVN repository:
   ```bash
   svn co https://plugins.svn.wordpress.org/privaro-cookie-consent-banner/assets privaro-assets
   ```
2. Copy files from this directory to the SVN `assets` folder.
3. Add new files to SVN:
   ```bash
   svn add * --force
   ```
4. Commit the changes:
   ```bash
   svn ci -m "Update assets"
   ```
