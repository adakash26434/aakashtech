# Aakash Technologies — Website with Admin & Client Panels

## Overview
A modern, next-generation company website for **Aakash Technologies**, a Nepal-based IT company offering:
- Bulk SMS and bulk auto voice calls for cooperatives, companies, parties, and personal notices, with volume rates
- Domain registration, hosting, and server management
- Domain email on Zoho for organizations
- Custom websites for companies, portfolios, cooperatives, restaurants, schools, hotels, and news portals
- On-site cyber security training for directors, staff, and members

Clients buy these plans from the client portal. Recurring plans renew from a wallet balance, so a person only confirms the incoming top-up.

Includes a full **Admin Panel** and **Client Portal** with authentication.

## Tech Stack
- **Frontend:** HTML5, Tailwind CSS (CDN), Alpine.js, Lucide icons, Space Grotesk + Inter
- **Backend:** PHP 8.x, written with PHP 7.4-compatible patterns
- **Database:** MySQL / MariaDB or SQLite
- **No build tools required** — the site runs directly from PHP

## Run locally

For a quick local preview with SQLite:

```bash
DB_DRIVER=sqlite SQLITE_PATH=/tmp/aakash-technologies.sqlite php -S 0.0.0.0:8080 -t . router.php
```

Open `http://localhost:8080`. The SQLite schema is created automatically the first time a page connects to the database. Set `SQLITE_PATH` to a location outside the public web root when deploying with SQLite.

Renewals also run when a client opens the portal. To renew every account once a day, even if nobody logs in:

```bash
php cron/renewals.php
```

Optional payment labels for the wallet page:

```text
ESEWA_ID=98XXXXXXXX
KHALTI_ID=98XXXXXXXX
BANK_DETAILS=Bank name, account name, account number
```

To trigger renewals over HTTP, set `CRON_KEY` and call `cron/renewals.php?key=...`.

## Install on cPanel

The site is meant to live inside `public_html`. Database name, database user, database password, and the admin login are written in `cpanel-config.php` in that same folder.

### Step 1: Upload
Upload every file into `public_html`. The domain should open `index.php`.

### Step 2: Create the database
1. cPanel → **MySQL Databases**
2. Create a database and a user, then add the user to that database with **All Privileges**
3. cPanel → **phpMyAdmin** → that database → **Import** → choose `database.sql`

### Step 3: Write the settings file
Open `public_html/cpanel-config.php` in File Manager → Edit. Fill in the cPanel database name, user, and password, plus the admin email and a password of at least 6 characters. Save the file, then open the website once. Admin login is `your-domain/admin/login.php` with that email and password.

Contact email, eSewa, Khalti, and bank text are in the same file. The browser cannot open `cpanel-config.php` or `config.php`.

After the first save in **Admin → Settings**, the public site uses the email, footer text, and the chat links from that screen. WhatsApp, Viber, and Messenger appear only after a real number or an https://m.me/ link is saved. Those numbers are not printed on the site, and the site does not publish a call number.

### Keep passwords out of Git (recommended)
Do not type real passwords into `cpanel-config.php`. Copy `cpanel-config.sample.php` to `cpanel-config.local.php` on the server and fill that file in. It is ignored by Git and its values win over `cpanel-config.php`. Set `site_url` to your public address: password-reset e-mails use it and no longer trust the request Host header.

### Step 4: Visit the site
Open the domain. Clients register from the client login page.

## File Structure
```
public_html/
├── index.php              (main website)
├── config.php             (database config + auth helpers)
├── database.sql           (MySQL / MariaDB setup — import once)
├── database.sqlite.sql    (SQLite setup — initialized automatically)
├── README.md              (this file)
├── .htaccess              (server config)
├── router.php             (local preview protection for internal files)
├── assets/
│   ├── css/site.css       (public website styles)
│   ├── css/style.css      (shared panel components)
│   ├── css/portal.css     (unified admin and client portal theme)
│   ├── js/site.js         (public website interactions)
│   └── js/portal.js       (portal Lucide icons)
├── includes/
│   ├── site-header.php    (shared public navigation)
│   ├── site-footer.php    (shared public footer)
│   └── sqlite-mysqli-compat.php (SQLite adapter for existing pages)
├── admin/                 (Admin Panel)
│   ├── login.php          (admin login page)
│   ├── index.php          (admin dashboard)
│   ├── inquiries.php      (manage contact form inquiries)
│   ├── clients.php        (manage registered clients)
│   ├── services.php       (manage service catalog)
│   ├── billing.php        (confirm top-ups, prices, renewals)
│   ├── campaigns.php      (view all SMS campaigns)
│   ├── tickets.php        (manage support tickets)
│   ├── settings.php       (site settings + change password)
│   ├── logout.php
│   └── includes/
│       ├── header.php
│       ├── sidebar.php
│       └── footer.php
└── client/                (Client Portal)
    ├── login.php          (client login + registration)
    ├── index.php          (client dashboard)
    ├── services.php       (view subscribed services)
    ├── shop.php           (buy service plans)
    ├── checkout.php       (pay for a plan from the wallet)
    ├── wallet.php         (balance, top-ups, SMS credits, voice calls)
    ├── campaigns.php      (create & view SMS campaigns)
    ├── sms-portal.php     (SMS portal username and password after credit is active)
    ├── support.php        (create & view support tickets)
    ├── profile.php        (edit profile + change password)
    ├── logout.php
    └── includes/
        ├── header.php
        ├── sidebar.php
        └── footer.php
```

## Default Credentials
- **Admin Panel:** `admin/login.php`
  - Email and password: the `admin_email` and `admin_password` lines in `cpanel-config.php`
- **Client Portal:** `client/login.php`
  - Clients can self-register via the registration form

## Features

### Main Website
- Responsive layout with clear service information and contact paths
- Shared public header and footer
- Tailwind CSS, Alpine.js, Lucide icons, and consistent typography
- Contact form with CSRF protection and database storage
- Email and WhatsApp links for a query, in the contact section and the footer. Both are edited in Admin → Settings. WhatsApp opens a chat with “Hello, I have a query.” Email opens a message titled “Query for” the site name.
- A popup notice the admin can turn on, edit, and turn off from Settings. Visitors can close it; the same text stays hidden until the browser is opened again.
- Footer social icons for WhatsApp, Facebook, Instagram, YouTube, TikTok, and LinkedIn. An icon appears only after its https link is saved.
- MySQL / MariaDB and SQLite support
- Reduced-motion support, keyboard focus styles, and SEO metadata
- Matching visual theme across Admin and Client portals, including responsive sidebars

### Admin Panel
- Secure login with password hashing (bcrypt)
- Dashboard with overview stats (inquiries, clients, services, tickets, campaigns)
- Manage inquiries (filter by status, mark as read)
- Manage clients (view, suspend/activate)
- Manage service catalog (add, enable/disable, delete)
- Confirm wallet top-ups and edit the prices clients pay (`admin/billing.php`)
- View all SMS campaigns
- Manage support tickets (reply, change status)
- Site settings (logo, name, email, phone, WhatsApp number, location, footer text, popup notice, and social links). The public site and both portals use these saved values.
- Change admin password

### Client Portal
- Self-registration with email/password
- Secure login with password hashing (bcrypt)
- Dashboard with personal stats
- Buy services and pay from a wallet balance
- Automatic renewal for monthly and yearly plans, with a 7-day grace period
- View subscribed services, SMS credits, and voice calls
- Create and manage SMS campaigns (with scheduling)
- Create support tickets and view admin replies
- Edit profile (name, phone, company, address)
- Change password

## Requirements
- PHP 7.4 or higher (PHP 8.x recommended)
- MySQL 5.7+ or MariaDB 10.3+ (optional when using SQLite)
- PHP `mysqli` extension when using MySQL / MariaDB
- PHP PDO and `pdo_sqlite` extensions when using SQLite
- Any web server (Apache, Nginx, LiteSpeed)

## Security Notes
- Passwords are hashed with PHP's `password_hash()` (bcrypt)
- Admin and client sign-in continue with Google Authenticator. The first sign-in shows a QR code and eight backup codes. Each backup code works once. Admin → Clients can reset a client's authenticator. If the only admin loses the phone and the backup codes, clear `totp_secret` for that admin row in the database, then sign in and set it up again.
- CSRF token protection on admin and public contact forms
- `.htaccess` blocks direct access to `config.php` and `.sql` files
- The local PHP preview router also blocks internal configuration and schema files
- Security headers set (X-Content-Type-Options, X-Frame-Options, etc.)
- Set a strong admin password before enabling admin sign-in

### Tests
`php tests/admin-pages-test.php` does the same for every admin page (and checks sub-folder file paths). Add `--hash` to print a fingerprint per page; run it on two versions of the code and `diff` the output to prove a refactor changed nothing visible.

`php tests/pages-test.php` opens every client page as a logged-in client (office view) and fails on any PHP error, so a broken include or path shows up before deploy.

`php tests/money-test.php` checks wallet debit, top-up approval, rollback and SMS credit rules on a temporary SQLite file. Run it before deploying any change to `includes/billing.php`.

### Styling build (Tailwind)
Pages use a pre-built `assets/css/tailwind.css` instead of the Tailwind CDN script (faster, works offline, no third-party script). After you add or change Tailwind classes in any `.php` file, rebuild it:
```bash
npm install
npm run build:css
```
Brand colours live in `assets/css/tokens.css` and `tailwind.config.js`; keep the two in step.

### Code layout: billing
`includes/billing.php` is now a small loader. The code lives in `includes/billing/`:
`core.php` (helpers, settings), `schema.php` (tables), `catalog.php` (plans, prices, pricing pages), `wallet.php` (wallet, units, ledger, top-ups, transactions), `kyc.php` (identity), `mail.php` (e-mail), `site.php` (public settings, logos), `orders.php` (purchases, refunds, renewals). Other files keep using `require 'includes/billing.php'` as before.

### Code layout: SMS
`includes/sms-gateway.php` is a small loader for `includes/sms/`: `core.php` (tables, templates, lists), `accounts.php` (identity gate, senders, credits), `contacts.php` (typed/CSV/Excel numbers), `vendor.php` (upstream provider), `delivery.php` (sending, schedule, queue), `logs.php` (history), `api.php` (public API), `voice.php`. Files in a sub-folder must use `dirname(__DIR__, 2)` to reach the project root.

### SMS delivery reports
When the phone network confirms delivery, the provider can call your site. In `cpanel-config.local.php` set `'dlr_key' => 'a-long-random-secret'`, then in the provider panel set the delivery-report (callback) URL to
`https://YOUR-SITE/api/sms-dlr.php?key=a-long-random-secret`.
The client SMS log then shows a green **Delivered** or red **Not delivered** badge next to *Sent*. Reports never change the sent/failed status or credits. Check with your provider that they offer delivery callbacks; if they do not, nothing changes and the badge simply stays hidden.

### Code layout: big pages
`client/sms-portal.php` keeps only the page markup. Its form handling is in `client/includes/sms-portal-actions.php`, its styles in `assets/css/sms-portal.css` and its script in `assets/js/sms-portal.js`. `client/login.php` loads `assets/js/client-login.js`.

`admin/settings.php`, `client.php`, `billing.php` and `sms-line.php` keep their markup; the form handling lives in `admin/includes/<page>-actions.php`.

### Scripts are self-hosted
Alpine.js (3.14.9) and Lucide icons (0.383.0) are served from `assets/vendor/`, pinned to exact versions, so pages no longer depend on unpkg or jsDelivr being reachable and a CDN change can never alter the site. To upgrade, replace the file and update the version in its name and in the `<script>` tags.

### Fonts
Inter and Space Grotesk (Latin) are self-hosted in `assets/fonts/` and declared in `assets/css/fonts.css`; there are no Google Fonts requests. Nepali text uses the device's Devanagari font through the fallback stack in `assets/css/tokens.css`.

### Daily database backup
Add one cPanel cron job, once a day: `php /home/USER/public_html/cron/backup.php`. It writes `backup-YYYY-MM-DD-HHMMSS.sql.gz`, keeps the newest 14 and deletes older ones. Files go to a folder next to the site (outside the web root); set `backup_dir` in `cpanel-config.local.php` to choose another. Restore: `gunzip -c backup-...sql.gz | mysql -u USER -p DBNAME`. The backup holds the database only; copy `uploads/` (KYC documents, logos) separately. Download a backup now and then and keep it somewhere other than this server.

### Behind Cloudflare or another proxy
If every visitor shows the same IP, set `'ip_header' => 'CF-Connecting-IP'` (or `X-Real-IP`) in `cpanel-config.local.php` so rate limits count each visitor separately. Leave it empty if the site can also be reached without the proxy, because the header could then be forged.

### When a page shows "Something went wrong"
Visitors see a short apology and a reference code such as `B074E955` (API calls get the same code in JSON). The full cause is in the server error log (cPanel → Errors, or `error_log` in the site folder) on a line starting `[ref B074E955]`. Ask the person for the code and search the log for it. Technical detail is never shown on screen.

### SMS dashboards
Client and admin dashboards open with an SMS panel: credits (or bulk stock against client credits), sent / delivered / failed for the last 7 days, a daily chart and quick actions. Numbers come from `includes/sms/stats.php`, markup from `includes/sms-overview-view.php`, style from `assets/css/sms-dash.css` (colours from `tokens.css`). The Delivered figure appears once the provider sends delivery reports (see "SMS delivery reports").
