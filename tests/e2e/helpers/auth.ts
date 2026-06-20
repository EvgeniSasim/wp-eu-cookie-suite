import { Page, expect } from '@playwright/test';

/**
 * Log in to WordPress.
 */
export async function loginToWordPress(page: Page) {
  const adminUser = process.env.WP_ADMIN_USER;
  const adminPass = process.env.WP_ADMIN_PASS;

  if (!adminUser || !adminPass) {
    throw new Error('WP_ADMIN_USER and WP_ADMIN_PASS environment variables must be set');
  }

  await page.goto('/wp-login.php');

  // Wait for the login form to be visible
  await page.waitForSelector('#loginform');

  await page.fill('#user_login', adminUser);
  await page.fill('#user_pass', adminPass);
  await page.click('#wp-submit');

  // Verify login was successful by checking for the dashboard or admin bar
  await expect(page.locator('#wpadminbar')).toBeVisible();
}

/**
 * Navigate to the plugin settings page.
 */
export async function goToPluginSettings(page: Page, tab: string = 'dashboard') {
  await page.goto(`/wp-admin/admin.php?page=wpeu-cs-settings&tab=${tab}`);
}
