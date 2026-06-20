import { test, expect } from '@playwright/test';

test.describe('Frontend Consent Flow', () => {
  test.beforeEach(async ({ page, context }) => {
    // Ensure a fresh context without admin cookies
    await context.clearCookies();
    await page.goto('/');
  });

  test('Initial state: no consent cookies set', async ({ context }) => {
    const cookies = await context.cookies();
    const consentCookies = cookies.filter(c => c.name.startsWith('wpeu_') && c.name !== 'wpeu_consent_uuid');
    expect(consentCookies.length).toBe(0);
  });

  test('Reject all: category cookies set to 0', async ({ page, context }) => {
    // Wait for banner to appear
    const banner = page.locator('#cc-main');
    await expect(banner).toBeVisible();

    // Click Reject All / Accept Necessary
    // The button text might vary but we can use the ID/class or data-cc
    await page.click('button[data-cc="accept-necessary"]');

    // Wait for cookies to be set
    await page.waitForFunction(() => {
        return document.cookie.includes('wpeu_statistics=0');
    });

    const cookies = await context.cookies();
    const statsCookie = cookies.find(c => c.name === 'wpeu_statistics');
    const marketingCookie = cookies.find(c => c.name === 'wpeu_marketing');

    expect(statsCookie?.value).toBe('0');
    expect(marketingCookie?.value).toBe('0');
  });

  test('Accept all: category cookies set to 1', async ({ page, context }) => {
    const banner = page.locator('#cc-main');
    await expect(banner).toBeVisible();

    await page.click('button[data-cc="accept-all"]');

    await page.waitForFunction(() => {
        return document.cookie.includes('wpeu_statistics=1');
    });

    const cookies = await context.cookies();
    const statsCookie = cookies.find(c => c.name === 'wpeu_statistics');
    const marketingCookie = cookies.find(c => c.name === 'wpeu_marketing');

    expect(statsCookie?.value).toBe('1');
    expect(marketingCookie?.value).toBe('1');
  });

  test('Revoke consent: cookies cleared and banner visible without reload', async ({ page, context }) => {
    // First accept all
    await expect(page.locator('#cc-main')).toBeVisible();
    await page.click('button[data-cc="accept-all"]');

    await page.waitForFunction(() => document.cookie.includes('wpeu_statistics=1'));

    // Now revoke via custom event
    await page.evaluate(() => {
      window.dispatchEvent(new CustomEvent('wpeu-cs-revoke'));
    });

    // Banner should become visible again
    await expect(page.locator('#cc-main')).toBeVisible();

    // Cookies should be cleared (or set to 0 depending on implementation, but typically cleared)
    // Actually, v1.1.2 fix was about showing banner without reload.
    const cookies = await context.cookies();
    const statsCookie = cookies.find(c => c.name === 'wpeu_statistics');

    // In our implementation, revoke usually clears them or resets.
    // If they are gone from the context.cookies(), that's one way.
    expect(statsCookie).toBeUndefined();
  });
});
