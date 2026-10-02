<?php

function service_pricing_defaults()
{
    return array(
        'bulk-sms' => array(
            'label' => 'Indicative rate',
            'amount' => 'NPR 0.65–0.95 per SMS',
            'details' => 'Lower per-message rates at higher volume.'
        ),
        'domain-hosting' => array(
            'label' => 'Typical yearly costs',
            'amount' => '',
            'details' => ".com domain — NPR 2,400/year\nHosting / server — from NPR 3,500/year\nStandard SSL — Often included\nPaid DV SSL — from NPR 5,000/year"
        ),
        'website-design' => array(
            'label' => 'Project pricing',
            'amount' => 'Custom quote',
            'details' => 'Based on pages, features and scope.'
        ),
        'cyber-security' => array(
            'label' => 'One-time team session',
            'amount' => 'NPR 30,000–50,000',
            'details' => 'Final quote depends on team size and session scope.'
        )
    );
}

function load_service_pricing($conn)
{
    $pricing = service_pricing_defaults();
    $prefix = 'service_pricing_';
    $result = $conn->query('SELECT setting_key, setting_value FROM site_settings');

    if (!$result) {
        throw new RuntimeException('Service pricing settings could not be read.');
    }

    while ($row = $result->fetch_assoc()) {
        $key = isset($row['setting_key']) ? (string) $row['setting_key'] : '';
        if (strpos($key, $prefix) !== 0) {
            continue;
        }
        $slug = substr($key, strlen($prefix));
        if (!array_key_exists($slug, $pricing)) {
            continue;
        }

        $stored = json_decode((string) ($row['setting_value'] ?? ''), true);
        if (!is_array($stored)) {
            continue;
        }

        foreach (array('label', 'amount', 'details') as $field) {
            if (isset($stored[$field]) && is_string($stored[$field])) {
                $value = $stored[$field];
                if ($field === 'details') {
                    $value = str_replace(array('\\r\\n', '\\n', '\\r'), array("\n", "\n", "\n"), $value);
                }
                $pricing[$slug][$field] = $value;
            }
        }
    }

    return $pricing;
}

function save_service_pricing($conn, $slug, $values)
{
    $defaults = service_pricing_defaults();
    if (!array_key_exists($slug, $defaults)) {
        throw new InvalidArgumentException('Unknown service pricing key.');
    }

    $storedValues = array(
        'label' => (string) ($values['label'] ?? ''),
        'amount' => (string) ($values['amount'] ?? ''),
        'details' => (string) ($values['details'] ?? '')
    );
    $encoded = json_encode($storedValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Service pricing could not be encoded.');
    }

    $settingKey = 'service_pricing_' . $slug;
    $check = $conn->prepare('SELECT id FROM site_settings WHERE setting_key = ?');
    $check->bind_param('s', $settingKey);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        $statement = $conn->prepare('UPDATE site_settings SET setting_value = ? WHERE setting_key = ?');
    } else {
        $statement = $conn->prepare('INSERT INTO site_settings (setting_value, setting_key) VALUES (?, ?)');
    }

    $statement->bind_param('ss', $encoded, $settingKey);
    $statement->execute();
    $statement->close();
}