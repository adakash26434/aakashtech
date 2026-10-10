// End-to-end sign-up flow in a real browser: sign up, open the emailed link, sign in,
// set up the authenticator with a code computed here, save the backup codes, reach the portal.
// Run against a local test server started with a throw-away database and a mail outbox:
//   DB_DRIVER=sqlite SQLITE_PATH=/tmp/e2e.sqlite MAIL_OUTBOX_DIR=/tmp/outbox php -S 127.0.0.1:8099 -t . router.php
//   BASE=http://127.0.0.1:8099 OUTBOX=/tmp/outbox node tests/e2e/signup-flow.js
const { chromium } = require('playwright');
const fs = require('fs'), crypto = require('crypto'), path = require('path');
const BASE = process.env.BASE || 'http://127.0.0.1:8099', OUT = process.env.OUTBOX;
if (!OUT) { console.error('Set OUTBOX to the MAIL_OUTBOX_DIR folder of the test server.'); process.exit(2); }
const email = 'e2e' + Date.now() + '@example.com', password = 'Test-Pass-2026', phone = '9841' + String(Date.now()).slice(-6);
function base32(s){const a='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';let bits='',out='';for(const c of s.replace(/\s/g,'').toUpperCase()){const v=a.indexOf(c);if(v>=0)bits+=v.toString(2).padStart(5,'0');}for(let i=0;i+5<=bits.length;i+=5)out+=a[parseInt(bits.slice(i,i+5),2)];return out;}
function b32bytes(secret){const a='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';let bits='';for(const c of secret.toUpperCase()){const v=a.indexOf(c);if(v>=0)bits+=v.toString(2).padStart(5,'0');}const bytes=[];for(let i=0;i+8<=bits.length;i+=8)bytes.push(parseInt(bits.slice(i,i+8),2));return Buffer.from(bytes);}
function totp(secret, t=Date.now()){const key=b32bytes(secret);const c=Buffer.alloc(8);c.writeBigUInt64BE(BigInt(Math.floor(t/1000/30)));const h=crypto.createHmac('sha1',key).update(c).digest();const o=h[h.length-1]&15;const code=((h[o]&127)<<24|h[o+1]<<16|h[o+2]<<8|h[o+3])%1000000;return String(code).padStart(6,'0');}
const log = (...a) => console.log('[e2e]', ...a);
(async () => {
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium', args: ['--no-sandbox'] });
  const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const p = await ctx.newPage();
  // 1. sign up
  await p.goto(BASE + '/client/login.php?action=register', { waitUntil: 'load' });
  const q = await p.locator('label[for=human_check]').innerText();
  const [, x, y] = q.match(/(\d+)\s*\+\s*(\d+)/);
  await p.fill('input[name=name]', 'E2E Client');
  await p.fill('input[name=email]', email);
  await p.fill('input[name=phone]', phone);
  await p.fill('input[name=password]', password);
  await p.fill('input[name=confirm_password]', password);
  await p.fill('input[name=human_check]', String(Number(x) + Number(y)));
  await p.check('input[name=accept_terms]');
  await p.click('button[name=register]');
  await p.waitForLoadState('load');
  const notice = await p.locator('body').innerText();
  log('signup notice shown:', /Account created\. We sent a confirmation link/.test(notice));
  // 2. the emailed link (dev outbox)
  const mails = fs.readdirSync(OUT).filter(f => f.endsWith('.eml')).sort();
  const mail = fs.readFileSync(path.join(OUT, mails[mails.length - 1]), 'utf8');
  const link = mail.match(/https?:\/\/[^\s]+verify-email\.php\?token=[a-f0-9]{64}/)[0].replace(/^https?:\/\/[^/]+/, BASE);
  log('mail to', mail.match(/To: (.*)/)[1].trim(), '| link found:', !!link);
  // 3. sign-in before confirming is refused
  await p.goto(BASE + '/client/login.php', { waitUntil: 'load' });
  await p.fill('input[name=email]', email); await p.fill('input[name=password]', password);
  await p.click('button[name=login]'); await p.waitForLoadState('load');
  log('before confirm, message:', /Confirm your email address first/.test(await p.locator('body').innerText()));
  // 4. open the link
  await p.goto(link, { waitUntil: 'load' });
  log('after link, notice:', /Email confirmed/.test(await p.locator('body').innerText()));
  // 5. sign in: authenticator setup (first time)
  await p.goto(BASE + '/client/login.php', { waitUntil: 'load' });
  await p.fill('input[name=email]', email); await p.fill('input[name=password]', password);
  await p.click('button[name=login]'); await p.waitForLoadState('load');
  log('now at:', p.url().split('/').pop());
  const secretText = await p.locator('p.font-mono').first().innerText();
  const secret = base32(secretText);
  log('setup secret read:', secret.length >= 16);
  await p.fill('input[name=authenticator_code]', totp(secret));
  await p.click('button[type=submit]');
  await p.waitForLoadState('load');
  log('after setup code, at:', p.url().split('/').pop());
  // backup codes page: acknowledge, then continue
  if (await p.locator('input[type=checkbox]').count()) {
    const codesShown = (await p.locator('body').innerText()).match(/backup code/i);
    log('backup codes page shown:', !!codesShown);
    await p.check('input[type=checkbox]');
    await p.locator('form button[type=submit]').last().click();
    await p.waitForLoadState('load');
    log('after backup codes, at:', p.url().split('/').pop());
  }
  // 6. terms, then the portal
  if (p.url().includes('accept-terms')) { await p.check('input[name=accept_terms]'); await p.click('button[type=submit]'); await p.waitForLoadState('load'); }
  log('final page:', p.url().split('/').pop(), '| welcome shown:', /Welcome/.test(await p.locator('body').innerText()));
  await b.close();
})().catch(e => { console.error('FAILED:', e.message.slice(0, 300)); process.exit(1); });
