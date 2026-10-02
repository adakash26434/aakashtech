PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'admin',
    is_active INTEGER DEFAULT 1,
    last_login TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS client_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    phone TEXT DEFAULT NULL,
    company TEXT DEFAULT NULL,
    address TEXT DEFAULT NULL,
    status TEXT DEFAULT 'active',
    avatar_color TEXT DEFAULT '#0b8b7a',
    sms_portal_username TEXT DEFAULT '',
    sms_portal_password TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    service TEXT DEFAULT NULL,
    message TEXT NOT NULL,
    status TEXT DEFAULT 'new',
    admin_notes TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sms_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    campaign_name TEXT NOT NULL,
    sender_id TEXT DEFAULT NULL,
    message_content TEXT NOT NULL,
    recipients_count INTEGER DEFAULT 0,
    channel TEXT DEFAULT 'sms',
    audience TEXT DEFAULT '',
    purpose TEXT DEFAULT '',
    recipients_list TEXT DEFAULT '',
    declaration_text TEXT DEFAULT '',
    status TEXT DEFAULT 'draft',
    scheduled_at TEXT DEFAULT NULL,
    sent_at TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS client_services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    service_name TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    status TEXT DEFAULT 'active',
    start_date TEXT DEFAULT NULL,
    end_date TEXT DEFAULT NULL,
    price NUMERIC DEFAULT 0,
    plan_code TEXT DEFAULT NULL,
    billing_cycle TEXT DEFAULT 'one_time',
    auto_renew INTEGER DEFAULT 0,
    next_renewal TEXT DEFAULT NULL,
    detail_label TEXT DEFAULT NULL,
    order_brief TEXT DEFAULT NULL,
    unit_kind TEXT DEFAULT '',
    unit_quantity INTEGER DEFAULT 0,
    grace_until TEXT DEFAULT NULL,
    last_attempt_on TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    subject TEXT NOT NULL,
    description TEXT NOT NULL,
    priority TEXT DEFAULT 'medium',
    status TEXT DEFAULT 'open',
    admin_reply TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL,
    icon TEXT DEFAULT NULL,
    features TEXT DEFAULT NULL,
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_admin_email ON admin_users(email);
CREATE INDEX IF NOT EXISTS idx_client_email ON client_users(email);
CREATE INDEX IF NOT EXISTS idx_client_status ON client_users(status);
CREATE INDEX IF NOT EXISTS idx_inquiry_status ON inquiries(status);
CREATE INDEX IF NOT EXISTS idx_inquiry_created ON inquiries(created_at);
CREATE INDEX IF NOT EXISTS idx_campaign_client ON sms_campaigns(client_id);
CREATE INDEX IF NOT EXISTS idx_ticket_client ON support_tickets(client_id);
CREATE INDEX IF NOT EXISTS idx_service_active ON services(is_active);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scope TEXT NOT NULL,
    ip TEXT NOT NULL,
    attempted_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS service_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    service_slug TEXT NOT NULL,
    name TEXT NOT NULL,
    summary TEXT NOT NULL,
    billing_cycle TEXT NOT NULL,
    price NUMERIC NOT NULL,
    offer_price NUMERIC DEFAULT 0,
    unit_kind TEXT DEFAULT '',
    unit_quantity INTEGER DEFAULT 0,
    auto_renew_default INTEGER DEFAULT 0,
    needs_detail TEXT DEFAULT '',
    is_active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS client_wallets (
    client_id INTEGER PRIMARY KEY,
    balance NUMERIC NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS wallet_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    amount NUMERIC NOT NULL,
    direction TEXT NOT NULL,
    kind TEXT NOT NULL,
    status TEXT NOT NULL,
    method TEXT DEFAULT '',
    reference_note TEXT DEFAULT '',
    related_service_id INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS client_units (
    client_id INTEGER NOT NULL,
    unit_kind TEXT NOT NULL,
    balance INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (client_id, unit_kind)
);

CREATE TABLE IF NOT EXISTS renewal_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_service_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    amount NUMERIC NOT NULL,
    result TEXT NOT NULL,
    note TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rate_slabs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_slug TEXT NOT NULL,
    min_qty INTEGER NOT NULL,
    max_qty INTEGER NOT NULL,
    unit_price NUMERIC NOT NULL,
    sort_order INTEGER DEFAULT 0,
    is_start INTEGER DEFAULT 0,
    offer_price NUMERIC DEFAULT 0
);

INSERT OR IGNORE INTO admin_users (name, email, password, role) VALUES
('Super Admin', 'admin@aakashtechnologies.com', 'RESET_ADMIN_PASSWORD_BEFORE_USE', 'super_admin');

INSERT OR IGNORE INTO services (title, slug, description, icon, features, sort_order) VALUES
('Bulk SMS Service', 'bulk-sms', 'Reach customers with campaigns, alerts, and scheduled messages.', 'message-square-text', 'Campaigns,Scheduling,Reporting', 1),
('Bulk Voice Call', 'bulk-voice', 'Send recorded voice calls for reminders, offers, and notices.', 'phone-call', 'Voice calls,Reminders,Minutes', 2),
('Domain Registration', 'domain-registration', 'Register a domain and renew it automatically.', 'globe', '.com,.com.np,Auto-renew', 3),
('Domain Hosting & Server Management', 'hosting-server', 'Hosting, SSL, and server care that stays online.', 'server', 'Hosting,SSL,Server care', 4),
('Professional Email', 'professional-email', 'Business mailboxes on your own domain.', 'mail', 'Mailboxes,Your domain,Auto-renew', 5),
('Cyber Security Training', 'cyber-security', 'Practical training that helps a team work more safely online.', 'shield-check', 'Awareness,Team session,Safe habits', 6);

INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Aakash Technologies'),
('site_email', 'info@aakashtechnologies.com'),
('site_phone', '+977 98XXXXXXXX'),
('whatsapp_number', ''),
('notice_enabled', '0'),
('notice_title', ''),
('notice_body', ''),
('notice_link', ''),
('notice_link_label', ''),
('notice_image', ''),
('facebook_url', ''),
('instagram_url', ''),
('youtube_url', ''),
('tiktok_url', ''),
('linkedin_url', ''),
('site_location', 'Kathmandu, Nepal'),
('footer_tagline', 'Practical technology for businesses ready to grow.'),
('footer_text', 'Designed and built in Nepal.'),
('logo_path', ''),
('esewa_id', ''),
('khalti_id', ''),
('bank_details', '');

INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES
('service_pricing_bulk-sms', '{"label":"Indicative rate","amount":"NPR 0.65–0.95 per SMS","details":"Lower per-message rates at higher volume."}'),
('service_pricing_domain-hosting', '{"label":"Typical yearly costs","amount":"","details":".com domain — NPR 2,400/year\\nHosting / server — from NPR 3,500/year\\nStandard SSL — Often included\\nPaid DV SSL — from NPR 5,000/year"}'),
('service_pricing_website-design', '{"label":"Project pricing","amount":"Custom quote","details":"Based on pages, features and scope."}'),
('service_pricing_cyber-security', '{"label":"One-time team session","amount":"NPR 30,000–50,000","details":"Final quote depends on team size and session scope."}');