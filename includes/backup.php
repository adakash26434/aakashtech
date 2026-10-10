<?php
/**
 * Database backups: a gzip-compressed SQL file you can restore with any MySQL/SQLite client.
 * Run by cron/backup.php. Keeps the newest few files and deletes older ones.
 */

function backup_dir()
{
    $configured = defined('BACKUP_DIR') ? trim((string) BACKUP_DIR) : '';
    $root = dirname(__DIR__);
    $candidates = array();
    if ($configured !== '') {
        $candidates[] = $configured;
    }
    // Best place: next to the site folder, outside the web root.
    $candidates[] = dirname($root) . '/aakash-backups';
    $candidates[] = $root . '/backups';
    foreach ($candidates as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            if (strpos(realpath($dir), realpath($root)) === 0) {
                // Inside the site folder: make sure the web server never serves it.
                @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
                @file_put_contents($dir . '/index.html', '');
            }
            return $dir;
        }
    }
    return '';
}

function backup_quote($conn, $value)
{
    if ($value === null) {
        return 'NULL';
    }
    $text = (string) $value;
    if (method_exists($conn, 'real_escape_string')) {
        return "'" . $conn->real_escape_string($text) . "'";
    }
    return "'" . str_replace("'", "''", $text) . "'";
}

function backup_tables($conn)
{
    $names = array();
    if (DB_DRIVER === 'sqlite') {
        $result = $conn->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    } else {
        $result = $conn->query('SHOW TABLES');
    }
    while ($result && ($row = $result->fetch_assoc())) {
        $values = array_values($row);
        $names[] = (string) $values[0];
    }
    return $names;
}

/**
 * Secrets that must never sit in a backup file: TOTP seeds and the key that unlocks saved
 * cPanel passwords. They are written as empty values; the live database keeps them.
 */
function backup_redact($table, $row)
{
    if (($table === 'client_users' || $table === 'admin_users') && array_key_exists('totp_secret', $row)) {
        $row['totp_secret'] = '';
    }
    if ($table === 'site_settings' && isset($row['setting_key']) && $row['setting_key'] === 'panel_cipher_key') {
        $row['setting_value'] = '';
    }
    return $row;
}

/** Writes the dump into $path (a .sql.gz file). Returns the number of rows saved, or -1 on failure. */
function backup_write($conn, $path)
{
    $gz = @gzopen($path, 'wb9');
    if (!$gz) {
        return -1;
    }
    $total = 0;
    gzwrite($gz, "-- Aakash Technologies backup " . date('Y-m-d H:i:s') . "\n");
    gzwrite($gz, DB_DRIVER === 'sqlite' ? "PRAGMA foreign_keys = OFF;\nBEGIN;\n" : "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n");
    foreach (backup_tables($conn) as $table) {
        if (DB_DRIVER === 'sqlite') {
            $schema = $conn->query("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '" . str_replace("'", "''", $table) . "'")->fetch_assoc();
            $schema = $schema ? array_values($schema) : array();
            $create = isset($schema[0]) ? $schema[0] : '';
            $quoted = '"' . str_replace('"', '""', $table) . '"';
        } else {
            $schema = $conn->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch_assoc();
            $schema = $schema ? array_values($schema) : array();
            $create = isset($schema[1]) ? $schema[1] : '';
            $quoted = '`' . str_replace('`', '``', $table) . '`';
        }
        gzwrite($gz, "\nDROP TABLE IF EXISTS $quoted;\n$create;\n");
        $rows = $conn->query("SELECT * FROM $quoted");
        $batch = array();
        $columns = '';
        while ($rows && ($row = $rows->fetch_assoc())) {
            $row = backup_redact($table, $row);
            if ($columns === '') {
                $columns = '(' . implode(', ', array_map(function ($c) { return DB_DRIVER === 'sqlite' ? '"' . $c . '"' : '`' . $c . '`'; }, array_keys($row))) . ')';
            }
            $batch[] = '(' . implode(', ', array_map(function ($v) use ($conn) { return backup_quote($conn, $v); }, array_values($row))) . ')';
            $total++;
            if (count($batch) >= 200) {
                gzwrite($gz, "INSERT INTO $quoted $columns VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = array();
            }
        }
        if ($batch) {
            gzwrite($gz, "INSERT INTO $quoted $columns VALUES\n" . implode(",\n", $batch) . ";\n");
        }
    }
    gzwrite($gz, DB_DRIVER === 'sqlite' ? "COMMIT;\n" : "SET FOREIGN_KEY_CHECKS = 1;\n");
    gzclose($gz);
    return $total;
}

function backup_run($conn, $keep = 14)
{
    $dir = backup_dir();
    if ($dir === '') {
        return array('ok' => false, 'message' => 'No writable backup folder. Set backup_dir in cpanel-config.local.php.');
    }
    $file = $dir . '/backup-' . date('Y-m-d-His') . '.sql.gz';
    $rows = backup_write($conn, $file);
    if ($rows < 0 || !is_file($file) || filesize($file) < 50) {
        @unlink($file);
        return array('ok' => false, 'message' => 'The backup file could not be written.');
    }
    @chmod($file, 0640);
    $old = glob($dir . '/backup-*.sql.gz') ?: array();
    sort($old);
    foreach (array_slice($old, 0, max(0, count($old) - max(1, (int) $keep))) as $stale) {
        @unlink($stale);
    }
    return array('ok' => true, 'file' => basename($file), 'rows' => $rows, 'bytes' => filesize($file), 'kept' => min(count($old), (int) $keep));
}
