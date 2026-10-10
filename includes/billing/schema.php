<?php
/**
 * Billing: Creates and upgrades the billing tables.
 * Split from the old includes/billing.php. Functions are unchanged.
 */

function billing_create_tables($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS service_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT NOT NULL UNIQUE,
            service_slug TEXT NOT NULL,
            name TEXT NOT NULL,
            summary TEXT NOT NULL,
            billing_cycle TEXT NOT NULL,
            price NUMERIC NOT NULL,
            offer_price NUMERIC DEFAULT 0,
            unit_kind TEXT DEFAULT "",
            unit_quantity INTEGER DEFAULT 0,
            auto_renew_default INTEGER DEFAULT 0,
            needs_detail TEXT DEFAULT "",
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_wallets (
            client_id INTEGER PRIMARY KEY,
            balance NUMERIC NOT NULL DEFAULT 0
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS wallet_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            amount NUMERIC NOT NULL,
            direction TEXT NOT NULL,
            kind TEXT NOT NULL,
            status TEXT NOT NULL,
            method TEXT DEFAULT "",
            reference_note TEXT DEFAULT "",
            related_service_id INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_units (
            client_id INTEGER NOT NULL,
            unit_kind TEXT NOT NULL,
            balance INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (client_id, unit_kind)
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS renewal_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_service_id INTEGER NOT NULL,
            client_id INTEGER NOT NULL,
            amount NUMERIC NOT NULL,
            result TEXT NOT NULL,
            note TEXT DEFAULT "",
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS rate_slabs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_slug TEXT NOT NULL,
            min_qty INTEGER NOT NULL,
            max_qty INTEGER NOT NULL,
            unit_price NUMERIC NOT NULL,
            sort_order INTEGER DEFAULT 0,
            is_start INTEGER DEFAULT 0,
            offer_price NUMERIC DEFAULT 0
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS site_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT DEFAULT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        billing_exec($conn, 'CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            description TEXT NOT NULL,
            icon TEXT DEFAULT NULL,
            features TEXT DEFAULT NULL,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        return;
    }

    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS service_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(80) NOT NULL UNIQUE,
        service_slug VARCHAR(80) NOT NULL,
        name VARCHAR(255) NOT NULL,
        summary TEXT NOT NULL,
        billing_cycle VARCHAR(20) NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        offer_price DECIMAL(12,2) NOT NULL DEFAULT 0,
        unit_kind VARCHAR(40) DEFAULT "",
        unit_quantity INT DEFAULT 0,
        auto_renew_default TINYINT(1) DEFAULT 0,
        needs_detail VARCHAR(20) DEFAULT "",
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_wallets (
        client_id INT PRIMARY KEY,
        balance DECIMAL(12,2) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS wallet_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        direction VARCHAR(10) NOT NULL,
        kind VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL,
        method VARCHAR(40) DEFAULT "",
        reference_note VARCHAR(255) DEFAULT "",
        related_service_id INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_wallet_client (client_id),
        INDEX idx_wallet_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS client_units (
        client_id INT NOT NULL,
        unit_kind VARCHAR(40) NOT NULL,
        balance INT NOT NULL DEFAULT 0,
        PRIMARY KEY (client_id, unit_kind)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS renewal_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_service_id INT NOT NULL,
        client_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        result VARCHAR(30) NOT NULL,
        note VARCHAR(255) DEFAULT "",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_renewal_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS rate_slabs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_slug VARCHAR(80) NOT NULL,
        min_qty INT NOT NULL,
        max_qty INT NOT NULL,
        unit_price DECIMAL(12,2) NOT NULL,
        sort_order INT DEFAULT 0,
        is_start TINYINT(1) NOT NULL DEFAULT 0,
        offer_price DECIMAL(12,2) NOT NULL DEFAULT 0,
        INDEX idx_slab_service (service_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    billing_exec($conn, 'CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        description TEXT NOT NULL,
        icon VARCHAR(100) DEFAULT NULL,
        features TEXT DEFAULT NULL,
        sort_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_active (is_active),
        INDEX idx_sort (sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function billing_add_missing_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'client_services'));
    if (DB_DRIVER === 'sqlite') {
        $columns = array(
            'plan_code' => 'TEXT DEFAULT NULL',
            'billing_cycle' => 'TEXT DEFAULT "one_time"',
            'auto_renew' => 'INTEGER DEFAULT 0',
            'next_renewal' => 'TEXT DEFAULT NULL',
            'detail_label' => 'TEXT DEFAULT NULL',
            'unit_kind' => 'TEXT DEFAULT ""',
            'unit_quantity' => 'INTEGER DEFAULT 0',
            'grace_until' => 'TEXT DEFAULT NULL',
            'last_attempt_on' => 'TEXT DEFAULT NULL',
            'order_brief' => 'TEXT DEFAULT NULL',
            'panel_user' => 'TEXT DEFAULT NULL',
            'panel_pass' => 'TEXT DEFAULT NULL',
            'panel_host' => 'TEXT DEFAULT NULL'
        );
    } else {
        $columns = array(
            'plan_code' => 'VARCHAR(80) DEFAULT NULL',
            'billing_cycle' => 'VARCHAR(20) DEFAULT "one_time"',
            'auto_renew' => 'TINYINT(1) DEFAULT 0',
            'next_renewal' => 'DATE DEFAULT NULL',
            'detail_label' => 'VARCHAR(255) DEFAULT NULL',
            'unit_kind' => 'VARCHAR(40) DEFAULT ""',
            'unit_quantity' => 'INT DEFAULT 0',
            'grace_until' => 'DATE DEFAULT NULL',
            'last_attempt_on' => 'DATE DEFAULT NULL',
            'order_brief' => 'TEXT DEFAULT NULL',
            'panel_user' => 'VARCHAR(32) DEFAULT NULL',
            'panel_pass' => 'TEXT DEFAULT NULL',
            'panel_host' => 'VARCHAR(253) DEFAULT NULL'
        );
    }

    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE client_services ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_ensure_portal_tables($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_services (
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
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_campaigns (
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
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            description TEXT NOT NULL,
            priority TEXT DEFAULT 'medium',
            status TEXT DEFAULT 'open',
            admin_reply TEXT DEFAULT NULL,
            client_followup TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS inquiries (
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
        )");
        return;
    }

    billing_exec($conn, "CREATE TABLE IF NOT EXISTS client_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        service_name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'active',
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        price DECIMAL(10,2) DEFAULT 0,
        plan_code VARCHAR(80) DEFAULT NULL,
        billing_cycle VARCHAR(20) DEFAULT 'one_time',
        auto_renew TINYINT(1) DEFAULT 0,
        next_renewal DATE DEFAULT NULL,
        detail_label VARCHAR(255) DEFAULT NULL,
        order_brief TEXT DEFAULT NULL,
        unit_kind VARCHAR(40) DEFAULT '',
        unit_quantity INT DEFAULT 0,
        grace_until DATE DEFAULT NULL,
        last_attempt_on DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS sms_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        campaign_name VARCHAR(255) NOT NULL,
        sender_id VARCHAR(20) DEFAULT NULL,
        message_content TEXT NOT NULL,
        recipients_count INT DEFAULT 0,
        channel VARCHAR(20) DEFAULT 'sms',
        audience VARCHAR(40) DEFAULT '',
        purpose VARCHAR(40) DEFAULT '',
        recipients_list MEDIUMTEXT,
        declaration_text TEXT,
        status VARCHAR(20) DEFAULT 'draft',
        scheduled_at DATETIME DEFAULT NULL,
        sent_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        priority VARCHAR(20) DEFAULT 'medium',
        status VARCHAR(20) DEFAULT 'open',
        admin_reply TEXT DEFAULT NULL,
        client_followup TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    billing_exec($conn, "CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        service VARCHAR(100) DEFAULT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'new',
        admin_notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function billing_ensure($conn)
{
    billing_ensure_portal_tables($conn);
    billing_create_tables($conn);
    billing_add_missing_columns($conn);
    $version = (int) billing_setting($conn, 'billing_schema_version');
    if ($version < 3 && DB_DRIVER !== 'sqlite') {
        billing_exec($conn, "ALTER TABLE client_services MODIFY status ENUM('active','expired','suspended','pending','past_due','booked','refunded') DEFAULT 'active'");
    }
    if ($version < 3) {
        billing_set_setting($conn, 'billing_schema_version', '3');
    }
    billing_add_campaign_columns($conn);
    billing_add_ticket_columns($conn);
    billing_drop_portal_columns($conn);
    billing_ensure_kyc_table($conn);
    billing_ensure_domain_requests($conn);
    billing_seed_plans($conn);
    billing_refresh_plans($conn);
    billing_seed_slabs($conn);
    billing_ensure_slab_start($conn);
    billing_ensure_offer_prices($conn);
    billing_sync_catalog($conn);
    if (function_exists('mail_login_scrub')) {
        mail_login_scrub($conn);
    }
    site_ensure_public_settings($conn);
    if (function_exists('sms_ensure_tables')) {
        sms_ensure_tables($conn);
    }
    billing_ensure_indexes($conn);
    if (function_exists('panel_pass_migrate')) {
        panel_pass_migrate($conn);
    }
}

function billing_add_campaign_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'sms_campaigns'));
    if (DB_DRIVER === 'sqlite') {
        $columns = array(
            'channel' => "TEXT DEFAULT 'sms'",
            'audience' => "TEXT DEFAULT ''",
            'purpose' => "TEXT DEFAULT ''",
            'recipients_list' => "TEXT DEFAULT ''",
            'declaration_text' => "TEXT DEFAULT ''",
            'language' => "TEXT DEFAULT ''",
            'api_ref' => 'TEXT DEFAULT NULL',
            'updated_at' => 'TEXT DEFAULT CURRENT_TIMESTAMP'
        );
    } else {
        $columns = array(
            'channel' => "VARCHAR(20) DEFAULT 'sms'",
            'audience' => "VARCHAR(40) DEFAULT ''",
            'purpose' => "VARCHAR(40) DEFAULT ''",
            'recipients_list' => 'MEDIUMTEXT',
            'declaration_text' => 'TEXT',
            'language' => "VARCHAR(20) DEFAULT ''",
            'api_ref' => 'VARCHAR(160) DEFAULT NULL',
            'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
        );
    }
    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE sms_campaigns ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_add_ticket_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'support_tickets'));
    if (isset($present['client_followup'])) {
        return;
    }
    $definition = DB_DRIVER === 'sqlite' ? 'TEXT DEFAULT NULL' : 'TEXT DEFAULT NULL';
    billing_exec($conn, 'ALTER TABLE support_tickets ADD COLUMN client_followup ' . $definition);
}

function billing_drop_portal_columns($conn)
{
    if (billing_setting($conn, 'portal_login_removed') === '1') {
        return;
    }
    $present = array_flip(billing_table_columns($conn, 'client_users'));
    $ok = true;
    foreach (array('sms_portal_username', 'sms_portal_password') as $name) {
        if (!isset($present[$name])) {
            continue;
        }
        try {
            billing_exec($conn, 'ALTER TABLE client_users DROP COLUMN ' . $name);
        } catch (Throwable $exception) {
            $ok = false;
            error_log('Old portal login column could not be removed.');
        }
    }
    if ($ok) {
        billing_set_setting($conn, 'portal_login_removed', '1');
    }
}

function billing_ensure_domain_requests($conn)
{
    if (DB_DRIVER === 'sqlite') {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS domain_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            domain_name TEXT NOT NULL,
            tld TEXT NOT NULL,
            holder_kind TEXT DEFAULT 'individual',
            holder_name TEXT DEFAULT '',
            document_path TEXT DEFAULT '',
            status TEXT DEFAULT 'requested',
            admin_note TEXT DEFAULT '',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            holder_address TEXT DEFAULT '',
            price TEXT DEFAULT '0.00',
            service_id INTEGER DEFAULT 0,
            activated_at TEXT DEFAULT NULL
        )");
    } else {
        billing_exec($conn, "CREATE TABLE IF NOT EXISTS domain_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            domain_name VARCHAR(190) NOT NULL,
            tld VARCHAR(20) NOT NULL,
            holder_kind VARCHAR(20) DEFAULT 'individual',
            holder_name VARCHAR(200) DEFAULT '',
            holder_address VARCHAR(200) DEFAULT '',
            document_path VARCHAR(255) DEFAULT '',
            price DECIMAL(12,2) DEFAULT 0,
            service_id INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'requested',
            admin_note TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activated_at DATETIME DEFAULT NULL,
            INDEX idx_domain_client (client_id),
            INDEX idx_domain_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    billing_ensure_domain_columns($conn);
}

function billing_ensure_domain_columns($conn)
{
    $present = array_flip(billing_table_columns($conn, 'domain_requests'));
    if (DB_DRIVER === 'sqlite') {
        $columns = array(
            'holder_address' => "TEXT DEFAULT ''",
            'price' => "TEXT DEFAULT '0.00'",
            'service_id' => "INTEGER DEFAULT 0"
        );
    } else {
        $columns = array(
            'holder_address' => "VARCHAR(200) DEFAULT ''",
            'price' => "DECIMAL(12,2) DEFAULT 0",
            'service_id' => "INT DEFAULT 0"
        );
    }
    foreach ($columns as $name => $definition) {
        if (isset($present[$name])) {
            continue;
        }
        billing_exec($conn, 'ALTER TABLE domain_requests ADD COLUMN ' . $name . ' ' . $definition);
    }
}

function billing_ensure_slab_start($conn)
{
    $present = array();
    if (DB_DRIVER === 'sqlite') {
        $result = billing_exec($conn, 'PRAGMA table_info(rate_slabs)');
        while ($row = $result->fetch_assoc()) {
            $present[(string) $row['name']] = true;
        }
    } else {
        $result = billing_exec($conn, 'SHOW COLUMNS FROM rate_slabs');
        while ($row = $result->fetch_assoc()) {
            $present[(string) $row['Field']] = true;
        }
    }
    if (!isset($present['is_start'])) {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN is_start INTEGER DEFAULT 0');
        } else {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN is_start TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
    foreach (array('bulk-sms', 'bulk-voice') as $slug) {
        $slabs = billing_load_slabs($conn, $slug);
        $chosen = 0;
        foreach ($slabs as $slab) {
            if (!empty($slab['is_start'])) {
                $chosen = (int) $slab['id'];
                break;
            }
        }
        if ($chosen !== 0 || !$slabs) {
            continue;
        }
        $id = (int) $slabs[0]['id'];
        $stmt = $conn->prepare('UPDATE rate_slabs SET is_start = 1 WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}

function billing_ensure_offer_prices($conn)
{
    if (!in_array('offer_price', billing_column_names($conn, 'rate_slabs'), true)) {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN offer_price NUMERIC DEFAULT 0');
        } else {
            billing_exec($conn, 'ALTER TABLE rate_slabs ADD COLUMN offer_price DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
    }
    if (!in_array('offer_price', billing_column_names($conn, 'service_plans'), true)) {
        if (DB_DRIVER === 'sqlite') {
            billing_exec($conn, 'ALTER TABLE service_plans ADD COLUMN offer_price NUMERIC DEFAULT 0');
        } else {
            billing_exec($conn, 'ALTER TABLE service_plans ADD COLUMN offer_price DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
    }
}
