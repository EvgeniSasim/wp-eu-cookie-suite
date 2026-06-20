# Privaro Cookie Consent Banner E2E Tests

This directory contains Playwright end-to-end tests for the Privaro Cookie Consent Banner plugin.

## Setup

1. Install dependencies:
   ```bash
   cd tests/e2e
   npm install
   ```

2. Configure environment variables. Create a `.env` file in this directory or set them in your environment:
   ```env
   WP_BASE_URL=http://localhost:8000
   WP_ADMIN_USER=admin
   WP_ADMIN_PASS=password
   ```

## Running Tests

To run all tests:
```bash
npm test
```

To run a specific test file:
```bash
npx playwright test specs/admin.spec.ts
```

To run in headed mode:
```bash
npx playwright test --headed
```

## Scenarios Covered

### Admin
- All 7 settings tabs load correctly.
- Changing banner title and saving.
- Banner preview iframe content and CSS.
- Integration toggles persistence.
- Tools toggles persistence.

### Frontend
- No consent cookies set on first visit.
- Rejecting all sets cookies to 0.
- Accepting all sets cookies to 1.
- Revoking consent via custom event clears cookies and shows banner without reload.
