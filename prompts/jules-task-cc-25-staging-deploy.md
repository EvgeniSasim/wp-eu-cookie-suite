# CC-25 Staging deploy + post-deploy cookie audit

```markdown
Task CC-25 — Document and script staging deployment for Privaro Cookie Consent Banner v1.1.2+.

## Goal

Unblock QA scenarios 20–21 (staging deploy + cookie audit). No plugin logic changes unless deploy reveals a bug.

## Deliverables

1. **`docs/staging-deploy.md`** — step-by-step:
   - Build zip: `bash scripts/build-release.sh`
   - Deactivate/remove legacy plugins: `wp-eu-cookie-suite`, `eu-cookie-consent-suite`
   - Install `privaro-cookie-consent-banner` from zip
   - Verify version header 1.1.2+ in admin
   - Reference WPSITE_* GitLab variables pattern from BSB `deployment_info.md` (generic, no secrets in repo)

2. **`scripts/verify-consent-audit.sh`** (optional bash):
   - curl homepage HTML/JS for `cc.run(ccConfig)` (not legacy-only `cc.onConsent(`)
   - Assert no `wpeu_statistics` in Set-Cookie before manual consent (best-effort)
   - Print pass/fail checklist for scenarios 15–19

3. **Post-deploy checklist** in doc:
   - Only one cookie plugin active
   - Frontend reject/accept/revoke manual smoke
   - drjv.org or staging URL cookie audit

## Constraints

- Never commit credentials, `.env`, or WPSITE file contents
- Do NOT bump plugin version
- PHPCS/PHPUnit must stay green

Branch: `jules/cc-25-staging-deploy`
```
