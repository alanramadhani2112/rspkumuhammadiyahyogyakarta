/**
 * Capture screenshots for the RS PKU Settings user guide.
 * 
 * Usage:
 *   npx playwright test docs/capture-screenshots.mjs
 * 
 * Or standalone:
 *   node docs/capture-screenshots.mjs
 * 
 * Prerequisites:
 *   - Laragon running with the site at http://rspkudev.test
 *   - Admin credentials (edit ADMIN_USER/ADMIN_PASS below)
 *   - npx playwright install chromium (first time only)
 */

import { chromium } from 'playwright';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { mkdirSync } from 'fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const IMAGES_DIR = join(__dirname, 'images');

// ─── CONFIG — WAJIB EDIT SEBELUM RUN ───
const BASE_URL = 'http://rspkudev.test';
const LOGIN_URL = `${BASE_URL}/rspku-log/`;  // ← ubah sesuai WPS Hide Login slug
const ADMIN_USER = 'admin';              // ← ubah username admin
const ADMIN_PASS = 'admin';              // ← ubah password admin
// ────────────────────────────────────────

mkdirSync(IMAGES_DIR, { recursive: true });

async function main() {
  console.log('🚀 Starting screenshot capture...\n');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
  });
  const page = await context.newPage();

  // ─── Login ───
  console.log('  → Logging in...');
  await page.goto(LOGIN_URL);
  await page.waitForLoadState('networkidle');
  
  // Try common WPS Hide Login field selectors
  const userField = await page.$('#user_login') || await page.$('input[name="log"]') || await page.$('input[type="text"]');
  const passField = await page.$('#user_pass') || await page.$('input[name="pwd"]') || await page.$('input[type="password"]');
  const submitBtn = await page.$('#wp-submit') || await page.$('input[type="submit"]');
  
  if (!userField || !passField) {
    console.error('  ✗ Cannot find login fields. Check LOGIN_URL is correct.');
    console.log('    Current URL:', page.url());
    await page.screenshot({ path: join(IMAGES_DIR, '00-debug-login.png') });
    console.log('    Saved debug screenshot to 00-debug-login.png');
    await browser.close();
    process.exit(1);
  }
  
  await userField.fill(ADMIN_USER);
  await passField.fill(ADMIN_PASS);
  await submitBtn.click();
  
  try {
    await page.waitForURL('**/wp-admin/**', { timeout: 15000 });
  } catch (e) {
    console.error('  ✗ Login failed — wrong credentials or redirect issue.');
    console.log('    Current URL after submit:', page.url());
    await page.screenshot({ path: join(IMAGES_DIR, '00-debug-after-login.png') });
    console.log('    Saved debug screenshot. Check credentials in script.');
    await browser.close();
    process.exit(1);
  }
  console.log('  ✓ Logged in\n');

  // ─── Screenshot: Login page ───
  // Go back to login page for screenshot (will redirect if already logged in)
  const loginPage = await context.newPage();
  await loginPage.goto(LOGIN_URL);
  await loginPage.waitForLoadState('networkidle');
  await loginPage.screenshot({ path: join(IMAGES_DIR, '01-login.png'), fullPage: false });
  await loginPage.close();
  console.log('  ✓ 01-login.png');

  // ─── Screenshot: Dashboard ───
  await page.goto(`${BASE_URL}/wp-admin/`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: join(IMAGES_DIR, '02-dashboard.png'), fullPage: false });
  console.log('  ✓ 02-dashboard.png');

  // ─── Screenshot: RS PKU Settings — Tab Umum ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=umum`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '03-settings-umum.png'), fullPage: true });
  console.log('  ✓ 03-settings-umum.png');

  // ─── Screenshot: Tab Kontak ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=kontak`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '04-settings-kontak.png'), fullPage: true });
  console.log('  ✓ 04-settings-kontak.png');

  // ─── Screenshot: Tab Homepage ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=homepage`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '05-settings-homepage.png'), fullPage: true });
  console.log('  ✓ 05-settings-homepage.png');

  // ─── Screenshot: Tab Gambar ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=gambar`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '06-settings-gambar.png'), fullPage: true });
  console.log('  ✓ 06-settings-gambar.png');

  // ─── Screenshot: Tab Fitur ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=features`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '07-settings-fitur.png'), fullPage: true });
  console.log('  ✓ 07-settings-fitur.png');

  // ─── Screenshot: Tab Header ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=header`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '08-settings-header.png'), fullPage: true });
  console.log('  ✓ 08-settings-header.png');

  // ─── Screenshot: Tab Footer ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=footer`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '09-settings-footer.png'), fullPage: true });
  console.log('  ✓ 09-settings-footer.png');

  // ─── Screenshot: Tab Tools ───
  await page.goto(`${BASE_URL}/wp-admin/admin.php?page=rspku-settings&tab=tools`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '10-settings-tools.png'), fullPage: true });
  console.log('  ✓ 10-settings-tools.png');

  // ─── Screenshot: Daftar Dokter ───
  await page.goto(`${BASE_URL}/wp-admin/edit.php?post_type=dokter`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: join(IMAGES_DIR, '11-dokter-list.png'), fullPage: false });
  console.log('  ✓ 11-dokter-list.png');

  // ─── Screenshot: Daftar Layanan ───
  await page.goto(`${BASE_URL}/wp-admin/edit.php?post_type=layanan`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: join(IMAGES_DIR, '12-layanan-list.png'), fullPage: false });
  console.log('  ✓ 12-layanan-list.png');

  // ─── Screenshot: Daftar Poliklinik ───
  await page.goto(`${BASE_URL}/wp-admin/edit.php?post_type=poliklinik`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: join(IMAGES_DIR, '13-poliklinik-list.png'), fullPage: false });
  console.log('  ✓ 13-poliklinik-list.png');

  // ─── Screenshot: Menu ───
  await page.goto(`${BASE_URL}/wp-admin/nav-menus.php`);
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: join(IMAGES_DIR, '14-menus.png'), fullPage: false });
  console.log('  ✓ 14-menus.png');

  // ─── Screenshot: Frontend Homepage ───
  await page.goto(BASE_URL);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000);
  await page.screenshot({ path: join(IMAGES_DIR, '15-frontend-homepage.png'), fullPage: false });
  console.log('  ✓ 15-frontend-homepage.png');

  // ─── Screenshot: Frontend Homepage full ───
  await page.screenshot({ path: join(IMAGES_DIR, '16-frontend-homepage-full.png'), fullPage: true });
  console.log('  ✓ 16-frontend-homepage-full.png');

  // ─── Screenshot: Frontend Dokter ───
  await page.goto(`${BASE_URL}/dokter/`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '17-frontend-dokter.png'), fullPage: false });
  console.log('  ✓ 17-frontend-dokter.png');

  // ─── Screenshot: Frontend Layanan ───
  await page.goto(`${BASE_URL}/layanan/`);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);
  await page.screenshot({ path: join(IMAGES_DIR, '18-frontend-layanan.png'), fullPage: false });
  console.log('  ✓ 18-frontend-layanan.png');

  await browser.close();
  console.log(`\n✅ Done! ${18} screenshots saved to docs/images/`);
  console.log('   Now update panduan-penggunaan-theme.html to reference these images.');
}

main().catch(err => {
  console.error('❌ Error:', err.message);
  process.exit(1);
});
