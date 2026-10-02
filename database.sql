-- ====== Aakash Technologies — Full Database Setup ======
-- Compatible with MySQL 5.7+ and MariaDB 10.3+
-- Import via phpMyAdmin or: mysql -u root -p < database.sql

CREATE DATABASE IF NOT EXISTS aakash_tech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aakash_tech;

-- ====== Admin Users ======
CREATE TABLE IF NOT EXISTS admin_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('super_admin', 'admin', 'staff') DEFAULT 'admin',
    is_active   TINYINT(1) DEFAULT 1,
    last_login  DATETIME DEFAULT NULL,
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
    sms_portal_username VARCHAR(80) DEFAULT '',
    sms_portal_password VARCHAR(80) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
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
    status          ENUM('draft', 'pending', 'scheduled', 'sending', 'sent', 'failed') DEFAULT 'draft',
    scheduled_at    DATETIME DEFAULT NULL,
    sent_at         DATETIME DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status)
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
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status)
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
('Bulk SMS Service', 'bulk-sms', 'Reach customers with campaigns, alerts, and scheduled messages.', 'message-square-text',
 'Campaigns,Scheduling,Reporting', 1),
('Bulk Voice Call', 'bulk-voice', 'Send recorded voice calls for reminders, offers, and notices.', 'phone-call',
 'Voice calls,Reminders,Minutes', 2),
('Domain Registration', 'domain-registration', 'Register a domain and renew it automatically.', 'globe',
 '.com,.com.np,Auto-renew', 3),
('Domain Hosting & Server Management', 'hosting-server', 'Hosting, SSL, and server care that stays online.', 'server',
 'Hosting,SSL,Server care', 4),
('Professional Email', 'professional-email', 'Business mailboxes on your own domain.', 'mail',
 'Mailboxes,Your domain,Auto-renew', 5),
('Cyber Security Training', 'cyber-security', 'Practical training that helps a team work more safely online.', 'shield-check',
 'Awareness,Team session,Safe habits', 6);

-- ====== Site Settings ======
CREATE TABLE IF NOT EXISTS site_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
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
('footer_text', 'Designed and built in Nepal.'),
('footer_tagline', 'Practical technology for businesses ready to grow.'),
('logo_path', ''),
('esewa_id', ''),
('khalti_id', ''),
('bank_details', '');

INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('service_pricing_bulk-sms', '{"label":"Indicative rate","amount":"NPR 0.65–0.95 per SMS","details":"Lower per-message rates at higher volume."}'),
('service_pricing_domain-hosting', '{"label":"Typical yearly costs","amount":"","details":".com domain — NPR 2,400/year\\nHosting / server — from NPR 3,500/year\\nStandard SSL — Often included\\nPaid DV SSL — from NPR 5,000/year"}'),
('service_pricing_website-design', '{"label":"Project pricing","amount":"Custom quote","details":"Based on pages, features and scope."}'),
('service_pricing_cyber-security', '{"label":"One-time team session","amount":"NPR 30,000–50,000","details":"Final quote depends on team size and session scope."}');

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
    INDEX idx_wallet_status (status)
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
    INDEX idx_slab_service (service_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
