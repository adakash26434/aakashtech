// End-to-end password reset in a real browser: request a reset for an active account, open the emailed link,
// check the token is not left in the address bar, save a new password and land on the sign-in page.
// Run against the same local test server as signup-flow.js, after that test has created an active account:
//   BASE=http://127.0.0.1:8099 OUTBOX=/tmp/outbox EMAIL=<the account email> node tests/e2e/reset-flow.js
const { chromium } = require('playwright');
const fs = require('fs');
const BASE = process.env.BASE || 'http://127.0.0.1:8099', OUT = process.env.OUTBOX, EMAIL = process.env.EMAIL;
if (!OUT || !EMAIL) { console.error('Set OUTBOX (MAIL_OUTBOX_DIR) and EMAIL (an active client account).'); process.exit(2); }
const log = (...a) => console.log('[reset-e2e]', ...a);
(async () => {
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium', args: ['--no-sandbox'] });
  const p = await (await b.newContext({ viewport: { width: 1280, height: 900 } })).newPage();
  const before = new Set(fs.readdirSync(OUT));
  await p.goto(BASE + '/client/forgot-password.php', { waitUntil: 'load' });
  await p.fill('input[name=email]', EMAIL);
  const [, x, y] = (await p.locator('label[for=human_check]').innerText()).match(/(\d+)\s*\+\s*(\d+)/);
  await p.fill('input[name=human_check]', String(Number(x) + Number(y)));
  await p.locator('button[type=submit]').first().click();
  await p.waitForLoadState('load');
  await new Promise(r => setTimeout(r, 800));
  let link = null;
  for (const f of fs.readdirSync(OUT).filter(f => !before.has(f))) {
    const m = fs.readFileSync(OUT + '/' + f, 'utf8').match(/https?:\/\/\S*reset-password\.php\?token=[a-f0-9]{64}/);
    if (m) link = m[0].replace(/=\r?\n/g, '');
  }
  log('reset link in outbox:', !!link);
  if (!link) { await b.close(); process.exit(1); }
  // The emailed link uses the site's canonical origin; point it at the test server.
  link = link.replace(/^https?:\/\/[^/]+/, BASE);
  await p.goto(link, { waitUntil: 'load' });
  const cleanUrl = !p.url().includes('token=');
  log('token kept out of the address bar:', cleanUrl);
  await p.fill('input[name=password]', 'Brand-New-Pass-2026');
  await p.fill('input[name=confirm_password]', 'Brand-New-Pass-2026');
  await p.locator('button[type=submit]').first().click();
  await p.waitForLoadState('load');
  const saved = new URL(p.url()).pathname.endsWith('/client/login.php') && (await p.innerText('body')).includes('Password saved');
  log('saved and back at sign-in:', saved);
  await b.close();
  process.exit(cleanUrl && saved ? 0 : 1);
})().catch(e => { console.error(e); process.exit(1); });
