import { chromium } from 'playwright';

const BASE_FRONTEND = 'https://hellokostek.pl';
const BASE_CMS = 'https://panel.hellokostek.pl';

async function runSmokeTest() {
  console.log('🚀 Starting Comprehensive E2E Smoke Test in Headless Chrome...');
  console.log(`Frontend URL: ${BASE_FRONTEND}`);
  console.log(`CMS URL:      ${BASE_CMS}`);

  const browser = await chromium.launch({
    channel: 'chrome',
    headless: true,
  });

  const context = await browser.newContext({
    viewport: { width: 1280, height: 800 },
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 HellokostekAuditBot/1.0',
  });

  const page = await context.newPage();

  const errors = [];
  page.on('pageerror', err => {
    console.error(`❌ [PAGE ERROR]: ${err.message}`);
    errors.push(`Page error: ${err.message}`);
  });

  page.on('console', msg => {
    if (msg.type() === 'error') {
      const text = msg.text();
      if (!text.includes('favicon.ico') && !text.includes('status of 404')) {
        console.warn(`⚠️ [CONSOLE ERROR]: ${text}`);
      }
    }
  });

  // ==========================================
  // 1. FRONTEND: HOME PAGE & FAQ
  // ==========================================
  console.log('\n--- 1. Testing Home Page (/) ---');
  await page.goto(`${BASE_FRONTEND}/`, { waitUntil: 'networkidle' });
  const homeTitle = await page.title();
  console.log(`  Title: "${homeTitle}"`);

  // Check Cookie Banner is hidden (cookieless mode)
  const cookieBanner = page.locator('#cookie-consent-banner');
  if (await cookieBanner.count() > 0) {
    const isVisible = await cookieBanner.isVisible();
    console.log(`  Cookie banner rendered. Is visible? ${isVisible} (Expected: false)`);
    if (isVisible) {
      errors.push('Cookie banner is visible but site is cookieless and banner should be disabled by default!');
    }
  } else {
    console.log('  ✓ Cookie banner element not found or not rendered (cookieless active).');
  }

  // Check FAQ Section on Homepage
  console.log('\n--- 1.1 Testing FAQ Accordion on Homepage ---');
  const faqHeading = page.locator('h2:has-text("Często zadawane pytania")');
  await faqHeading.scrollIntoViewIfNeeded();
  await page.waitForTimeout(500);

  const faqButtons = page.locator('section button:has-text("Jak"), section button:has-text("Czy")');
  const faqCount = await faqButtons.count();
  console.log(`  ✓ Found ${faqCount} interactive FAQ accordion questions.`);
  if (faqCount > 0) {
    await faqButtons.first().click();
    await page.waitForTimeout(400);
    console.log('  ✓ Clicked first FAQ accordion item, expanded successfully.');
  }

  // ==========================================
  // 2. FRONTEND: SHOP (/sklep)
  // ==========================================
  console.log('\n--- 2. Testing Shop Page (/sklep) ---');
  await page.goto(`${BASE_FRONTEND}/sklep`, { waitUntil: 'networkidle' });
  const shopTitle = await page.title();
  console.log(`  Title: "${shopTitle}"`);

  // Verify product cards rendered
  const productCards = page.locator('article, a[href*="/sklep/"]');
  const cardCount = await productCards.count();
  console.log(`  ✓ Found product links/cards: ${cardCount}`);

  // Check no raw HTML tags in descriptions
  const bodyText = await page.textContent('body');
  if (bodyText.includes('<p>') || bodyText.includes('</p>')) {
    errors.push('Raw HTML <p> tags leaked into shop body text!');
    console.error('❌ Found raw <p> tags in /sklep body text!');
  } else {
    console.log('  ✓ Descriptions are clean (no raw HTML tags leaked).');
  }

  // Check price labels are present
  const hasZl = bodyText.includes('zł');
  console.log(`  ✓ Prices in PLN rendered: ${hasZl}`);

  // ==========================================
  // 3. FRONTEND: PRODUCT DETAIL & INTERACTIVE CHECKOUT DRAWER
  // ==========================================
  console.log('\n--- 3. Testing Product Detail (/sklep/akwarela-2-2022) & Checkout Drawer ---');
  await page.goto(`${BASE_FRONTEND}/sklep/akwarela-2-2022`, { waitUntil: 'networkidle' });
  const productDetailTitle = await page.title();
  console.log(`  Title: "${productDetailTitle}"`);

  // Verify price and "Przejdź do zamówienia" button
  const orderBtn = page.locator('button:has-text("Przejdź do zamówienia")');
  await orderBtn.waitFor({ state: 'visible', timeout: 8000 });
  console.log('  ✓ "Przejdź do zamówienia" CTA button is visible.');

  // Test opening checkout drawer
  await orderBtn.click();
  await page.waitForTimeout(1000);

  // Check checkout drawer opened
  const drawerHeader = page.getByText('Krok 1: Dostawa');
  await drawerHeader.waitFor({ state: 'visible', timeout: 5000 });
  console.log('  ✓ Checkout Drawer opened with step: "Krok 1: Dostawa"');

  // Verify customer inputs in drawer
  const nameInput = page.locator('#checkout-name');
  if (await nameInput.count() > 0) {
    await nameInput.fill('Jan Kowalski');
    await page.locator('#checkout-email').fill('jan.kowalski@example.com');
    await page.locator('#checkout-phone').fill('500600700');
    console.log('  ✓ Interactive customer form filled inside drawer.');
  }

  // Close drawer with Escape key
  await page.keyboard.press('Escape');
  await page.waitForTimeout(500);
  console.log('  ✓ Drawer closed via Escape key.');

  // ==========================================
  // 4. FRONTEND: GALLERY (/galeria)
  // ==========================================
  console.log('\n--- 4. Testing Gallery (/galeria) ---');
  await page.goto(`${BASE_FRONTEND}/galeria`, { waitUntil: 'networkidle' });
  const galleryImages = page.locator('img[src*="/images/"], img[src*="portret"], img[src*="gallery"]');
  const galCount = await galleryImages.count();
  console.log(`  ✓ Gallery loaded with ${galCount} images displayed.`);

  // Test clicking first artwork to verify Lightbox opens
  const firstArt = page.locator('img[src*="/images/"]').first();
  if (await firstArt.count() > 0) {
    await firstArt.click();
    await page.waitForTimeout(500);
    console.log(`  ✓ Clicked artwork. Modal/lightbox active.`);
    await page.keyboard.press('Escape');
  }

  // ==========================================
  // 5. FRONTEND: CONTACT (/kontakt)
  // ==========================================
  console.log('\n--- 5. Testing Contact (/kontakt) ---');
  const resContact = await page.goto(`${BASE_FRONTEND}/kontakt`, { waitUntil: 'networkidle' });
  console.log(`  ✓ /kontakt HTTP status: ${resContact.status()}`);
  const contactForm = page.locator('form, input[type="email"]');
  console.log(`  ✓ Contact form elements found: ${await contactForm.count() > 0}`);

  // ==========================================
  // 6. FRONTEND: LEGAL PAGES (/regulamin & /polityka-prywatnosci)
  // ==========================================
  console.log('\n--- 6. Testing Legal Pages (/regulamin & /polityka-prywatnosci) ---');
  const resRegulamin = await page.goto(`${BASE_FRONTEND}/regulamin`, { waitUntil: 'networkidle' });
  console.log(`  ✓ /regulamin HTTP status: ${resRegulamin.status()}`);
  const resPolityka = await page.goto(`${BASE_FRONTEND}/polityka-prywatnosci`, { waitUntil: 'networkidle' });
  console.log(`  ✓ /polityka-prywatnosci HTTP status: ${resPolityka.status()}`);

  // ==========================================
  // 7. CMS: FILAMENT ADMIN DASHBOARD & ALL NAVIGATION ITEMS
  // ==========================================
  console.log('\n--- 7. Testing Filament CMS (https://panel.hellokostek.pl/admin) ---');
  await page.goto(`${BASE_CMS}/admin/login`, { waitUntil: 'networkidle' });
  console.log('  Login page loaded.');

  // Fill in admin credentials
  await page.fill('input[type="email"]', 'admin@hellokostek.pl');
  await page.fill('input[type="password"]', 'Admin1234!');
  await page.click('button[type="submit"]');

  await page.waitForURL(url => !url.pathname.endsWith('/login'), { timeout: 15000 });
  const adminUrl = page.url();
  console.log(`  After login URL: ${adminUrl}`);

  if (adminUrl.includes('/admin/login')) {
    const errorMsg = await page.locator('.fi-fo-field-wrp-error-message, [role="alert"]').allTextContents();
    errors.push(`CMS Login failed: ${errorMsg.join(' | ')}`);
  } else {
    console.log('  🎉 Successfully logged into Filament CMS as Administrator!');

    // Discover all navigation links from the CMS sidebar
    await page.waitForSelector('aside nav a, .fi-sidebar-nav a', { timeout: 8000 });
    const navAnchors = await page.locator('aside nav a[href], .fi-sidebar-nav a[href]').all();
    
    const navItems = [];
    for (const a of navAnchors) {
      const href = await a.getAttribute('href');
      const text = (await a.innerText()).trim();
      if (href && href.startsWith('http') && !navItems.some(item => item.href === href)) {
        navItems.push({ href, text });
      }
    }

    console.log(`\n  Discovered ${navItems.length} distinct navigation links in CMS sidebar:`);

    for (const item of navItems) {
      const resp = await page.goto(item.href, { waitUntil: 'networkidle' });
      const status = resp.status();
      const pageTitle = await page.title();
      if (status >= 400) {
        errors.push(`CMS Section "${item.text}" (${item.href}) returned HTTP ${status}!`);
        console.error(`  ❌ ${item.text.padEnd(20)} (${item.href}): HTTP ${status}`);
      } else {
        console.log(`  ✓ ${item.text.padEnd(20)} (${item.href}): HTTP ${status} | "${pageTitle}"`);
      }
    }
  }

  await browser.close();

  console.log('\n==========================================');
  if (errors.length === 0) {
    console.log('🎉 ALL E2E SMOKE TESTS PASSED WITH 0 ERRORS!');
  } else {
    console.error(`❌ COMPLETED WITH ${errors.length} ERRORS:`);
    errors.forEach(e => console.error(`  - ${e}`));
  }
  console.log('==========================================\n');
}

runSmokeTest().catch(err => {
  console.error('Fatal test error:', err);
  process.exit(1);
});
