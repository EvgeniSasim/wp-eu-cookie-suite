# Third-party software

Privaro Cookie Consent Banner bundles or depends on the following third-party components.

## Bundled in release zip

### vanilla-cookieconsent v3

- **License:** MIT
- **Author:** Orest Bida
- **Repository:** https://github.com/orestbida/cookieconsent
- **Bundled as:** `assets/js/cookieconsent.bundle.js`, `assets/css/cookieconsent.bundle.css`
- **Source entry:** `assets/js/src/cookieconsent.js` (import); rebuild with `npm run build` in the plugin directory.

The MIT license text is included in the upstream package (`node_modules/vanilla-cookieconsent/LICENSE` during development). The bundled files are committed so production installs do not require Node.js.

## PHP development dependencies (not shipped)

Installed via Composer for contributors only; excluded from the release zip by `.distignore`:

- PHPUnit, PHP_CodeSniffer, WPCS, PHPCompatibility

## WordPress APIs

- **WP Consent API** — optional companion plugin; this project registers compatibility and ships a polyfill when the API plugin is not active.

## External services (site owner configuration)

The plugin does not phone home. It may block or allow scripts/embeds configured by the site owner. See `readme.txt` section **External services** for disclosure text shown on WordPress.org.
