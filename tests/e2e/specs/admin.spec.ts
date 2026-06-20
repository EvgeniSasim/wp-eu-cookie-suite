import { test, expect } from '@playwright/test';
import { loginToWordPress, goToPluginSettings } from '../helpers/auth';

test.describe('Admin Settings', () => {
  test.beforeEach(async ({ page }) => {
    await loginToWordPress(page);
  });

  test('All 7 tabs load correctly', async ({ page }) => {
    const tabs = ['dashboard', 'banner', 'cookies', 'scanner', 'consent_log', 'integrations', 'tools'];

    for (const tab of tabs) {
      await goToPluginSettings(page, tab);
      const activeTab = page.locator('.nav-tab-active');
      await expect(activeTab).toBeVisible();
      // Tab names in UI might be different from internal slugs (e.g., 'consent_log' vs 'Consent Log')
      // but the slug should be in the URL.
      await expect(page).toHaveURL(new RegExp(`tab=${tab}`));
    }
  });

  test('Banner tab: change title and save', async ({ page }) => {
    await goToPluginSettings(page, 'banner');

    const titleInput = page.locator('input[name*="[consent_modal_title]"]');
    const newTitle = 'E2E Test Cookie Consent ' + Date.now();

    await titleInput.fill(newTitle);
    await page.click('input[type="submit"]#submit');

    // Verify success notice
    await expect(page.locator('.notice-success')).toBeVisible();

    // Verify persistence
    await page.reload();
    await expect(titleInput).toHaveValue(newTitle);
  });

  test('Banner preview iframe: verify content and primary color', async ({ page }) => {
    await goToPluginSettings(page, 'banner');

    const primaryColorInput = page.locator('#wpeu-cs-banner-primary-color');
    const primaryColor = '#ff0000';

    // Using color picker might be tricky, but we can try filling the input
    await primaryColorInput.fill(primaryColor);
    // Trigger change event if necessary
    await primaryColorInput.evaluate(e => e.dispatchEvent(new Event('change', { bubbles: true })));

    // Wait for preview to refresh (it uses AJAX)
    await page.click('#wpeu-cs-refresh-preview');

    const previewIframe = page.frameLocator('#wpeu-cs-banner-preview');

    // Verify CookieConsent structure in iframe
    // Based on vanilla-cookieconsent v3, it should have #cc-main
    await expect(previewIframe.locator('#cc-main')).toBeVisible({ timeout: 10000 });

    // Verify primary color CSS variable
    const body = previewIframe.locator('body');
    const colorVar = await body.evaluate((el) => {
      return getComputedStyle(el).getPropertyValue('--cc-btn-primary-bg').trim();
    });

    // The color might be slightly normalized by the browser
    // but should be our red if injected correctly.
    // However, the AJAX preview might need to be saved first OR it should reflect unsaved changes.
    // Looking at admin.js/Admin.php, it seems it sends current field values.
    expect(colorVar.toLowerCase()).toBe(primaryColor.toLowerCase());
  });

  test('Integrations: toggle blocker persistence', async ({ page }) => {
    await goToPluginSettings(page, 'integrations');

    const blockerCheckbox = page.locator('input[name*="[blocker_enabled]"]');
    const isChecked = await blockerCheckbox.isChecked();

    // Toggle
    if (isChecked) {
      await blockerCheckbox.uncheck();
    } else {
      await blockerCheckbox.check();
    }

    await page.click('input[type="submit"]#submit');
    await expect(page.locator('.notice-success')).toBeVisible();

    await page.reload();
    if (isChecked) {
      await expect(blockerCheckbox).not.toBeChecked();
    } else {
      await expect(blockerCheckbox).toBeChecked();
    }
  });

  test('Tools: toggle consent logging persistence', async ({ page }) => {
    await goToPluginSettings(page, 'tools');

    const loggingCheckbox = page.locator('input[name*="[consent_logging_enabled]"]');
    const isChecked = await loggingCheckbox.isChecked();

    // Toggle
    if (isChecked) {
      await loggingCheckbox.uncheck();
    } else {
      await loggingCheckbox.check();
    }

    // Note: Tools tab has multiple submit buttons.
    // The one for logging is after the logging settings.
    await loggingCheckbox.locator('xpath=ancestor::form').locator('input[type="submit"]').first().click();

    await expect(page.locator('.notice-success')).toBeVisible();

    await page.reload();
    if (isChecked) {
      await expect(loggingCheckbox).not.toBeChecked();
    } else {
      await expect(loggingCheckbox).toBeChecked();
    }
  });
});
