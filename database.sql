-- ====== Aakash Technologies — Full Database Setup ======
-- Compatible with MySQL 5.7+ and MariaDB 10.3+
-- cPanel: phpMyAdmin मा आफ्नो डाटाबेस छानेर यो फाइल import गर्नुहोस्।
-- नयाँ डाटाबेस बनाउने आदेश छैन। साझा होस्टिङले त्यो अनुमति दिँदैन।

-- ====== Admin Users ======
CREATE TABLE IF NOT EXISTS admin_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('super_admin', 'admin', 'staff') DEFAULT 'admin',
    is_active   TINYINT(1) DEFAULT 1,
    last_login  DATETIME DEFAULT NULL,
    totp_secret VARCHAR(64) NOT NULL DEFAULT '',
    totp_last_step INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The seeded account cannot sign in until its password is set; see README Step 4.
INSERT IGNORE INTO admin_users (name, email, password, role) VALUES
('Super Admin', 'admin@aakashtechnologies.com', 'RESET_ADMIN_PASSWORD_BEFORE_USE', 'super_admin');

-- ====== Client Users ======
CREATE TABLE IF NOT EXISTS client_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(50) DEFAULT NULL,
    company     VARCHAR(255) DEFAULT NULL,
    address     TEXT DEFAULT NULL,
    status      ENUM('active', 'suspended', 'pending') DEFAULT 'active',
    avatar_color VARCHAR(20) DEFAULT '#06b6d4',
    totp_secret VARCHAR(64) NOT NULL DEFAULT '',
    totp_last_step INT NOT NULL DEFAULT 0,
    login_notice_at DATETIME DEFAULT NULL,
    login_notice_ip VARCHAR(45) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_client_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_recovery_codes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    account_kind VARCHAR(10) NOT NULL,
    account_id   INT NOT NULL,
    code_hash    VARCHAR(255) NOT NULL,
    used_at      DATETIME DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recovery_account (account_kind, account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    INDEX idx_reset_hash (token_hash),
    INDEX idx_reset_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Inquiries (from main site contact form) ======
CREATE TABLE IF NOT EXISTS inquiries (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL,
    phone       VARCHAR(50) NOT NULL,
    service     VARCHAR(100) DEFAULT NULL,
    message     TEXT NOT NULL,
    status      ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    admin_notes TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== SMS Campaigns ======
CREATE TABLE IF NOT EXISTS sms_campaigns (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    campaign_name   VARCHAR(255) NOT NULL,
    sender_id       VARCHAR(20) DEFAULT NULL,
    message_content TEXT NOT NULL,
    recipients_count INT DEFAULT 0,
    channel         VARCHAR(20) DEFAULT 'sms',
    audience        VARCHAR(40) DEFAULT '',
    purpose         VARCHAR(40) DEFAULT '',
    recipients_list TEXT,
    declaration_text TEXT,
    language        VARCHAR(20) DEFAULT '',
    status          ENUM('draft', 'pending', 'scheduled', 'sending', 'sent', 'failed') DEFAULT 'draft',
    scheduled_at    DATETIME DEFAULT NULL,
    sent_at         DATETIME DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_sms_campaign_due (channel, status, scheduled_at),
    INDEX idx_sms_campaign_client_status (client_id, channel, status),
    INDEX idx_sms_campaign_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Client Services (what client has subscribed to) ======
CREATE TABLE IF NOT EXISTS client_services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    status      ENUM('active', 'expired', 'suspended', 'pending', 'past_due', 'booked', 'refunded') DEFAULT 'active',
    start_date  DATE DEFAULT NULL,
    end_date    DATE DEFAULT NULL,
    price       DECIMAL(10,2) DEFAULT 0,
    plan_code   VARCHAR(80) DEFAULT NULL,
    billing_cycle VARCHAR(20) DEFAULT 'one_time',
    auto_renew  TINYINT(1) DEFAULT 0,
    next_renewal DATE DEFAULT NULL,
    detail_label VARCHAR(255) DEFAULT NULL,
    order_brief TEXT DEFAULT NULL,
    unit_kind   VARCHAR(40) DEFAULT '',
    unit_quantity INT DEFAULT 0,
    grace_until DATE DEFAULT NULL,
    last_attempt_on DATE DEFAULT NULL,
    panel_user  VARCHAR(32) DEFAULT NULL,
    panel_pass  TEXT DEFAULT NULL,
    panel_host  VARCHAR(253) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_service_client_status (client_id, status),
    INDEX idx_service_renew (auto_renew, status, next_renewal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Support Tickets ======
CREATE TABLE IF NOT EXISTS support_tickets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority    ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status      ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    admin_reply TEXT DEFAULT NULL,
    client_followup TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Services (catalog) ======
CREATE TABLE IF NOT EXISTS services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    icon        VARCHAR(100) DEFAULT NULL,
    features    TEXT DEFAULT NULL,
    sort_order  INT DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO services (title, slug, description, icon, features, sort_order) VALUES
('Bulk SMS Service', 'bulk-sms', 'Informational SMS for cooperatives, companies, parties, and personal use, priced by volume.', 'message-square-text', 'AGM,Election,Festival', 1),
('Bulk Voice Call', 'bulk-voice', 'Auto voice calls for the same notices, priced by volume.', 'phone-call', 'Auto call,Volume slabs', 2),
('Domain Registration', 'domain-registration', 'Register a .com name, or a Nepal name such as .com.np or .coop.np, and renew it from the wallet.', 'globe', '.com,.com.np,.coop.np,Auto-renew', 3),
('Domain Hosting & Server Management', 'hosting-server', 'Website hosting and server management in Nepal.', 'server', 'Hosting,SSL,Server care', 4),
('Professional Email', 'professional-email', 'Mailboxes on your own domain, managed in Nepal.', 'mail', 'Your domain,Mailboxes,Auto-renew', 5),
('Custom Websites', 'custom-websites', 'Company, portfolio, cooperative, restaurant, school, hotel, and news websites.', 'panels-top-left', 'Company,School,Hotel,News', 6),
('Cyber Security Training', 'cyber-security', 'On-site training for directors, staff, and members.', 'shield-check', 'Directors,Staff,Members', 7);

-- ====== Site Settings ======
CREATE TABLE IF NOT EXISTS site_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
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
('footer_text', 'Designed and built in Nepal.'),
('footer_tagline', 'Practical technology for businesses ready to grow.'),
('logo_path', ''),
('esewa_id', ''),
('khalti_id', ''),
('bank_details', ''),
('privacy_policy', ''),
('cookie_policy', '');

-- ====== Login attempts ======
CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    scope       VARCHAR(20) NOT NULL,
    ip          VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_attempt_lookup (scope, ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Service plans (checkout catalog) ======
CREATE TABLE IF NOT EXISTS service_plans (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(80) NOT NULL UNIQUE,
    service_slug VARCHAR(80) NOT NULL,
    name        VARCHAR(255) NOT NULL,
    summary     TEXT NOT NULL,
    billing_cycle VARCHAR(20) NOT NULL,
    price       DECIMAL(12,2) NOT NULL,
    offer_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit_kind   VARCHAR(40) DEFAULT '',
    unit_quantity INT DEFAULT 0,
    auto_renew_default TINYINT(1) DEFAULT 0,
    needs_detail VARCHAR(20) DEFAULT '',
    is_active   TINYINT(1) DEFAULT 1,
    sort_order  INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Wallets ======
CREATE TABLE IF NOT EXISTS client_wallets (
    client_id   INT PRIMARY KEY,
    balance     DECIMAL(12,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_entries (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    direction   VARCHAR(10) NOT NULL,
    kind        VARCHAR(20) NOT NULL,
    status      VARCHAR(20) NOT NULL,
    method      VARCHAR(40) DEFAULT '',
    reference_note VARCHAR(255) DEFAULT '',
    related_service_id INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_client (client_id),
    INDEX idx_wallet_status (status),
    INDEX idx_wallet_kind_status (kind, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS client_units (
    client_id   INT NOT NULL,
    unit_kind   VARCHAR(40) NOT NULL,
    balance     INT NOT NULL DEFAULT 0,
    PRIMARY KEY (client_id, unit_kind)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Renewals ======
CREATE TABLE IF NOT EXISTS renewal_events (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_service_id INT NOT NULL,
    client_id   INT NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    result      VARCHAR(30) NOT NULL,
    note        VARCHAR(255) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_renewal_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== SMS and voice volume rates ======
CREATE TABLE IF NOT EXISTS rate_slabs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    service_slug VARCHAR(80) NOT NULL,
    min_qty     INT NOT NULL,
    max_qty     INT NOT NULL,
    unit_price  DECIMAL(12,2) NOT NULL,
    sort_order  INT DEFAULT 0,
    is_start    TINYINT(1) NOT NULL DEFAULT 0,
    offer_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    INDEX idx_slab_service (service_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Sender identity. Required before the SMS portal login is shown. ======
CREATE TABLE IF NOT EXISTS client_kyc (
    client_id   INT NOT NULL PRIMARY KEY,
    account_kind VARCHAR(20) DEFAULT '',
    status      VARCHAR(20) DEFAULT '',
    purpose     TEXT,
    full_name   VARCHAR(160) DEFAULT '',
    id_kind     VARCHAR(40) DEFAULT '',
    id_number   VARCHAR(80) DEFAULT '',
    address     TEXT,
    org_name    VARCHAR(200) DEFAULT '',
    registration_number VARCHAR(80) DEFAULT '',
    tax_number  VARCHAR(80) DEFAULT '',
    contact_name VARCHAR(160) DEFAULT '',
    contact_id_kind VARCHAR(40) DEFAULT '',
    contact_id_number VARCHAR(80) DEFAULT '',
    doc_identity VARCHAR(255) DEFAULT '',
    doc_registration VARCHAR(255) DEFAULT '',
    doc_tax     VARCHAR(255) DEFAULT '',
    doc_authority VARCHAR(255) DEFAULT '',
    admin_note  TEXT,
    submitted_at DATETIME DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kyc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== Domain registration requests. The team registers the name, then marks it active. ======
CREATE TABLE IF NOT EXISTS domain_requests (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    domain_name VARCHAR(190) NOT NULL,
    tld         VARCHAR(20) NOT NULL,
    holder_kind VARCHAR(20) DEFAULT 'individual',
    holder_name VARCHAR(200) DEFAULT '',
    holder_address VARCHAR(200) DEFAULT '',
    document_path VARCHAR(255) DEFAULT '',
    price       DECIMAL(12,2) DEFAULT 0,
    service_id  INT DEFAULT 0,
    status      VARCHAR(20) DEFAULT 'requested',
    admin_note  TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at DATETIME DEFAULT NULL,
    INDEX idx_domain_client (client_id),
    INDEX idx_domain_status (status),
    INDEX idx_domain_open (domain_name, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====== SMS dashboard: client tokens, delivery log, sender names. The upstream token is a site setting. ======
CREATE TABLE IF NOT EXISTS sms_api_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    label       VARCHAR(80) NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    token_prefix VARCHAR(16) NOT NULL,
    status      VARCHAR(20) DEFAULT 'active',
    allowed_ips VARCHAR(255) DEFAULT '',
    last_used_at DATETIME DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_sms_token_hash (token_hash),
    INDEX idx_sms_token_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_api_hits (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    token_id    INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_hit_token (token_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    campaign_id INT DEFAULT 0,
    token_id    INT DEFAULT 0,
    source      VARCHAR(20) DEFAULT 'dashboard',
    sender_id   VARCHAR(20) DEFAULT '',
    recipient   VARCHAR(20) NOT NULL,
    message_text TEXT NOT NULL,
    parts       INT DEFAULT 1,
    status      VARCHAR(20) DEFAULT 'queued',
    error_text  VARCHAR(40) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at     DATETIME DEFAULT NULL,
    INDEX idx_sms_msg_client (client_id, created_at),
    INDEX idx_sms_msg_campaign (campaign_id),
    INDEX idx_sms_msg_status (status),
    INDEX idx_sms_msg_client_status (client_id, status, created_at),
    INDEX idx_sms_msg_sent_day (client_id, status, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_sender_names (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    sender_name VARCHAR(20) NOT NULL,
    status      VARCHAR(20) DEFAULT 'pending',
    admin_note  VARCHAR(255) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_sender_client (client_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_templates (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    label       VARCHAR(80) NOT NULL,
    message_text TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_template_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_credit_notes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    credits     INT NOT NULL,
    note        VARCHAR(180) DEFAULT '',
    reversed_at DATETIME DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_credit_client (client_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_number_lists (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    label       VARCHAR(80) NOT NULL,
    numbers_text TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_list_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
