PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'admin',
    is_active INTEGER DEFAULT 1,
    last_login TEXT DEFAULT NULL,
    totp_secret TEXT DEFAULT '',
    totp_last_step INTEGER DEFAULT 0,
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
    totp_secret TEXT DEFAULT '',
    totp_last_step INTEGER DEFAULT 0,
    login_notice_at TEXT DEFAULT NULL,
    login_notice_ip TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS auth_recovery_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    account_kind TEXT NOT NULL,
    account_id INTEGER NOT NULL,
    code_hash TEXT NOT NULL,
    used_at TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_recovery_account ON auth_recovery_codes (account_kind, account_id);

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
    language TEXT DEFAULT '',
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
    panel_user TEXT DEFAULT NULL,
    panel_pass TEXT DEFAULT NULL,
    panel_host TEXT DEFAULT NULL,
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
    client_followup TEXT DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL,
    expires_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_reset_hash ON password_resets(token_hash);
CREATE INDEX IF NOT EXISTS idx_reset_client ON password_resets(client_id);

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

CREATE TABLE IF NOT EXISTS client_kyc (
    client_id INTEGER PRIMARY KEY,
    account_kind TEXT DEFAULT '',
    status TEXT DEFAULT '',
    purpose TEXT DEFAULT '',
    full_name TEXT DEFAULT '',
    id_kind TEXT DEFAULT '',
    id_number TEXT DEFAULT '',
    address TEXT DEFAULT '',
    org_name TEXT DEFAULT '',
    registration_number TEXT DEFAULT '',
    tax_number TEXT DEFAULT '',
    contact_name TEXT DEFAULT '',
    contact_id_kind TEXT DEFAULT '',
    contact_id_number TEXT DEFAULT '',
    doc_identity TEXT DEFAULT '',
    doc_registration TEXT DEFAULT '',
    doc_tax TEXT DEFAULT '',
    doc_authority TEXT DEFAULT '',
    doc_identity_back TEXT DEFAULT '',
    doc_clearance TEXT DEFAULT '',
    doc_photo TEXT DEFAULT '',
    details TEXT DEFAULT '',
    admin_note TEXT DEFAULT '',
    submitted_at TEXT DEFAULT NULL,
    reviewed_at TEXT DEFAULT NULL,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS domain_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    domain_name TEXT NOT NULL,
    tld TEXT NOT NULL,
    holder_kind TEXT DEFAULT 'individual',
    holder_name TEXT DEFAULT '',
    holder_address TEXT DEFAULT '',
    document_path TEXT DEFAULT '',
    price TEXT DEFAULT '0.00',
    service_id INTEGER DEFAULT 0,
    status TEXT DEFAULT 'requested',
    admin_note TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    activated_at TEXT DEFAULT NULL
);

INSERT OR IGNORE INTO admin_users (name, email, password, role) VALUES
('Super Admin', 'admin@aakashtechnologies.com', 'RESET_ADMIN_PASSWORD_BEFORE_USE', 'super_admin');

INSERT OR IGNORE INTO services (title, slug, description, icon, features, sort_order) VALUES
('Bulk SMS Service', 'bulk-sms', 'Informational SMS for cooperatives, companies, parties, and personal use, priced by volume.', 'message-square-text', 'AGM,Election,Festival', 1),
('Bulk Voice Call', 'bulk-voice', 'Auto voice calls for the same notices, priced by volume.', 'phone-call', 'Auto call,Volume slabs', 2),
('Domain Registration', 'domain-registration', 'Register a .com name, or a Nepal name such as .com.np or .coop.np, and renew it from the wallet.', 'globe', '.com,.com.np,.coop.np,Auto-renew', 3),
('Domain Hosting & Server Management', 'hosting-server', 'Website hosting and server management in Nepal.', 'server', 'Hosting,SSL,Server care', 4),
('Professional Email', 'professional-email', 'Mailboxes on your own domain, managed in Nepal.', 'mail', 'Your domain,Mailboxes,Auto-renew', 5),
('Custom Websites', 'custom-websites', 'Company, portfolio, cooperative, restaurant, school, hotel, and news websites.', 'panels-top-left', 'Company,School,Hotel,News', 6),
('Cyber Security Training', 'cyber-security', 'On-site training for directors, staff, and members.', 'shield-check', 'Directors,Staff,Members', 7);

INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Aakash Technologies'),
('site_email', 'info@aakashtechnologies.com.np'),
('notify_email', 'info@aakashtechnologies.com.np'),
('mail_from', 'noreply@aakashtechnologies.com.np'),
('site_phone', ''),
('whatsapp_number', ''),
('viber_number', ''),
('messenger_url', ''),
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
('bank_details', ''),
('privacy_policy', ''),
('cookie_policy', '');

CREATE INDEX IF NOT EXISTS idx_domain_client ON domain_requests(client_id);
CREATE INDEX IF NOT EXISTS idx_domain_status ON domain_requests(status);
CREATE INDEX IF NOT EXISTS idx_wallet_client ON wallet_entries(client_id);
CREATE INDEX IF NOT EXISTS idx_wallet_status ON wallet_entries(status);

CREATE TABLE IF NOT EXISTS sms_api_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    label TEXT NOT NULL,
    token_hash TEXT NOT NULL UNIQUE,
    token_prefix TEXT NOT NULL,
    status TEXT DEFAULT 'active',
    allowed_ips TEXT DEFAULT '',
    last_used_at TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sms_api_hits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token_id INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sms_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    campaign_id INTEGER DEFAULT 0,
    token_id INTEGER DEFAULT 0,
    source TEXT DEFAULT 'dashboard',
    sender_id TEXT DEFAULT '',
    recipient TEXT NOT NULL,
    message_text TEXT NOT NULL,
    parts INTEGER DEFAULT 1,
    status TEXT DEFAULT 'queued',
    error_text TEXT DEFAULT '',
    delivery TEXT DEFAULT '',
    delivery_at TEXT DEFAULT NULL,
    provider_ref TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    sent_at TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS sms_sender_names (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    sender_name TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    admin_note TEXT DEFAULT '',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_sms_token_client ON sms_api_tokens(client_id);
CREATE INDEX IF NOT EXISTS idx_sms_hit_token ON sms_api_hits(token_id, created_at);
CREATE INDEX IF NOT EXISTS idx_sms_msg_client ON sms_messages(client_id, created_at);
CREATE INDEX IF NOT EXISTS idx_sms_msg_campaign ON sms_messages(campaign_id);
CREATE INDEX IF NOT EXISTS idx_sms_msg_client_status ON sms_messages(client_id, status, created_at);
CREATE INDEX IF NOT EXISTS idx_sms_msg_sent_day ON sms_messages(client_id, status, sent_at);
CREATE INDEX IF NOT EXISTS idx_sms_campaign_due ON sms_campaigns(channel, status, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_sms_campaign_client_status ON sms_campaigns(client_id, channel, status);
CREATE INDEX IF NOT EXISTS idx_sms_campaign_created ON sms_campaigns(created_at);
CREATE INDEX IF NOT EXISTS idx_service_client_status ON client_services(client_id, status);
CREATE INDEX IF NOT EXISTS idx_service_renew ON client_services(auto_renew, status, next_renewal);
CREATE INDEX IF NOT EXISTS idx_wallet_kind_status ON wallet_entries(kind, status);
CREATE INDEX IF NOT EXISTS idx_domain_open ON domain_requests(domain_name, status);
CREATE INDEX IF NOT EXISTS idx_kyc_status ON client_kyc(status);
CREATE INDEX IF NOT EXISTS idx_client_created ON client_users(created_at);
CREATE INDEX IF NOT EXISTS idx_attempt_lookup ON login_attempts(scope, ip, attempted_at);
CREATE INDEX IF NOT EXISTS idx_sms_sender_client ON sms_sender_names(client_id, status);

CREATE TABLE IF NOT EXISTS sms_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    label TEXT NOT NULL,
    message_text TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sms_credit_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    credits INTEGER NOT NULL,
    note TEXT DEFAULT '',
    reversed_at TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_sms_credit_client ON sms_credit_notes(client_id, created_at);

CREATE TABLE IF NOT EXISTS sms_number_lists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    label TEXT NOT NULL,
    numbers_text TEXT NOT NULL,
    list_kind TEXT DEFAULT 'program',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_sms_template_client ON sms_templates(client_id);
CREATE INDEX IF NOT EXISTS idx_sms_list_client ON sms_number_lists(client_id);