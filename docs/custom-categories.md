# Custom consent categories

Site owners can define up to **5 custom consent categories** in **EU Cookie Suite → Banner → Categories**.

## When to use

Use custom categories when your site needs consent groups beyond the four built-in types:

- `necessary` (always on, not disableable)
- `preferences`
- `statistics`
- `marketing`

Examples: `social` (embedded widgets), `embeds` (third-party players), `affiliate` (partner tracking).

## Integration map

Each custom category must choose **Counts as (for blocking integrations)**:

| Value | Used for |
|-------|----------|
| `preferences` | WP Consent `preferences`, GCM `functionality_storage` |
| `statistics` | WP Consent `statistics`, GCM `analytics_storage` |
| `marketing` | WP Consent `marketing`, GCM ad storage flags |

Custom slugs still get their own cookies (`wpeu_{slug}`) and appear as separate toggles in the banner when enabled.

Script blocking checks the **custom slug directly** (`wpeu_cs_user_has_consent( 'social' )`), while WP Consent API and Google Consent Mode use the integration map with OR logic against built-in categories.

## Limits

- Max **5** custom categories
- Slug: 2–32 chars, `a-z`, `0-9`, `_`, `-`
- Built-in slugs cannot be reused
- Visibility in the banner is controlled by **Enabled Categories** checkboxes (not a separate per-category enabled flag)

## Developer filter

`wpeu_consent_categories` still applies after built-in and custom categories are merged.

## Import / export

Custom categories are included in JSON settings export/import under `custom_categories`.
