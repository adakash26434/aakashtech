<?php
/**
 * SMS: Reading numbers from typed text, CSV and Excel; personalising messages.
 * Split from the old includes/sms-gateway.php. Functions are unchanged.
 */

/**
 * Splits one typed line into names and numbers. Numbers typed with spaces ("+977 984 100 0001",
 * "98410 00001") are joined back into one number when that makes a valid Nepal mobile.
 * Keep in step with smsTokens() in assets/js/sms-portal.js.
 */
function sms_tokens($line)
{
    $valid = function ($digits) {
        if (strlen($digits) === 13 && substr($digits, 0, 3) === '977') {
            $digits = substr($digits, 3);
        }
        if (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }
        return preg_match('/^9[78]\d{8}$/', $digits) === 1;
    };
    $out = array();
    foreach (preg_split('/[,;]+/', (string) $line) as $chunk) {
        $acc = '';
        $raws = array();
        $flush = function () use (&$acc, &$raws, &$out, $valid) {
            if (!$raws) {
                return;
            }
            if (count($raws) > 1 && !$valid($acc)) {
                foreach ($raws as $raw) {
                    $out[] = $raw;
                }
            } else {
                $out[] = count($raws) > 1 ? $acc : $raws[0];
            }
            $acc = '';
            $raws = array();
        };
        foreach (preg_split('/\s+/', trim($chunk)) as $token) {
            if ($token === '') {
                continue;
            }
            if (!preg_match('/\d/', $token) || preg_match('/[^\d+\-().]/', $token)) {
                $flush();
                $out[] = $token;
                continue;
            }
            $digits = preg_replace('/\D/', '', $token);
            if ($acc !== '' && $valid($acc)) {
                $flush();
            }
            if ($acc !== '' && strlen($acc . $digits) > 13) {
                $flush();
            }
            $acc .= $digits;
            $raws[] = $token;
        }
        $flush();
    }
    return $out;
}

function sms_collect_contacts($raw, $max = 0)
{
    $max = (int) $max > 0 ? (int) $max : sms_send_limit();
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $contacts = array();
    if (!is_array($lines)) {
        return array('ok' => false, 'error' => 'Add at least one mobile number.', 'contacts' => array());
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = sms_tokens($line);
        $numbers = array();
        $words = array();
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (!preg_match('/\d/', $part)) {
                $words[] = $part;
                continue;
            }
            $digits = auth_mobile_number($part);
            if (!preg_match('/^9[78]\d{8}$/', $digits)) {
                return array('ok' => false, 'error' => '“' . substr($part, 0, 20) . '” is not a 10-digit Nepal mobile. Fix or remove it, then send again.', 'contacts' => array());
            }
            $numbers[$digits] = $digits;
        }
        $name = sms_contact_name(implode(' ', $words));
        foreach ($numbers as $digits) {
            if (!isset($contacts[$digits])) {
                $contacts[$digits] = array('number' => $digits, 'name' => $name);
            }
        }
    }
    $contacts = array_values($contacts);
    if (count($contacts) < 1) {
        return array('ok' => false, 'error' => 'Add at least one mobile number.', 'contacts' => array());
    }
    if (count($contacts) > $max) {
        return array('ok' => false, 'error' => 'Send at most ' . number_format($max) . ' numbers at a time. This list has ' . number_format(count($contacts)) . '.', 'contacts' => array());
    }
    return array('ok' => true, 'error' => '', 'contacts' => $contacts);
}

function sms_collect_numbers($raw, $max = 0)
{
    $parsed = sms_collect_contacts($raw, $max);
    $numbers = array();
    if (!empty($parsed['ok'])) {
        foreach ($parsed['contacts'] as $contact) {
            $numbers[] = $contact['number'];
        }
    }
    return array(
        'ok' => !empty($parsed['ok']),
        'error' => isset($parsed['error']) ? $parsed['error'] : '',
        'numbers' => $numbers
    );
}

function sms_import_cell($value)
{
    $value = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8'));
    $value = strtr($value, array(
        '०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
        '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9'
    ));
    return trim($value);
}

function sms_import_header_kind($cell)
{
    $cell = strtolower(sms_import_cell($cell));
    $cell = str_replace(array('_', '-'), ' ', $cell);
    if (in_array($cell, array('mobile', 'phone', 'number', 'contact', 'मोबाइल', 'नम्बर', 'सम्पर्क'), true)) {
        return 'mobile';
    }
    if (in_array($cell, array('firstname', 'first name', 'fname', 'नाम'), true)) {
        return 'first';
    }
    if (in_array($cell, array('lastname', 'last name', 'lname', 'surname', 'थर'), true)) {
        return 'last';
    }
    if (in_array($cell, array('name', 'full name', 'पूरा नाम'), true)) {
        return 'name';
    }
    return '';
}

function sms_import_lines($rows)
{
    $cleanRows = array();
    foreach ($rows as $cells) {
        if (!is_array($cells)) {
            continue;
        }
        $clean = array();
        foreach ($cells as $cell) {
            $clean[] = sms_import_cell($cell);
        }
        $cleanRows[] = $clean;
    }
    $headerAt = -1;
    $map = array();
    foreach ($cleanRows as $index => $cells) {
        $kinds = array();
        foreach ($cells as $position => $cell) {
            $kind = sms_import_header_kind($cell);
            if ($kind !== '') {
                $kinds[$kind] = $position;
            }
        }
        if (isset($kinds['mobile'])) {
            $headerAt = $index;
            $map = $kinds;
            break;
        }
    }
    $lines = array();
    $seen = array();
    foreach ($cleanRows as $index => $cells) {
        if ($index === $headerAt) {
            continue;
        }
        $number = '';
        $name = '';
        if ($map) {
            $mobileCell = isset($cells[$map['mobile']]) ? $cells[$map['mobile']] : '';
            $digits = auth_mobile_number($mobileCell);
            if (preg_match('/^9[78]\d{8}$/', $digits)) {
                $number = $digits;
            }
            $first = isset($map['first'], $cells[$map['first']]) ? $cells[$map['first']] : '';
            $last = isset($map['last'], $cells[$map['last']]) ? $cells[$map['last']] : '';
            $full = isset($map['name'], $cells[$map['name']]) ? $cells[$map['name']] : '';
            $name = trim($first . ' ' . $last);
            if ($name === '') {
                $name = $full;
            }
        } else {
            $words = array();
            foreach ($cells as $cell) {
                if ($cell === '' || sms_import_header_kind($cell) !== '') {
                    continue;
                }
                $digits = auth_mobile_number($cell);
                if (preg_match('/^9[78]\d{8}$/', $digits)) {
                    $number = $digits;
                    continue;
                }
                if (!preg_match('/\d/', $cell)) {
                    $words[] = $cell;
                }
            }
            $name = implode(' ', $words);
        }
        if ($number === '' || isset($seen[$number])) {
            continue;
        }
        $seen[$number] = true;
        $name = sms_contact_name($name);
        $lines[] = $name === '' ? $number : $name . ', ' . $number;
        if (count($lines) >= sms_send_limit()) {
            break;
        }
    }
    return implode("\n", $lines);
}

function sms_sample_workbook($kind)
{
    if ($kind === 'names') {
        $rows = array(
            array('firstname', 'lastname', 'mobile'),
            array('Ram', 'Bahadur', '9801000001'),
            array('Sita', 'Devi', '9801000002')
        );
        $filename = 'sms-names.xlsx';
    } else {
        $rows = array(
            array('mobile'),
            array('9801000001'),
            array('9801000002')
        );
        $filename = 'sms-mobile.xlsx';
    }
    $shared = array();
    $sheetRows = array();
    $rowNumber = 1;
    foreach ($rows as $row) {
        $cells = array();
        $column = 0;
        foreach ($row as $value) {
            $letter = chr(65 + $column);
            $index = array_search($value, $shared, true);
            if ($index === false) {
                $shared[] = $value;
                $index = count($shared) - 1;
            }
            $cells[] = '<c r="' . $letter . $rowNumber . '" t="s"><v>' . $index . '</v></c>';
            $column++;
        }
        $sheetRows[] = '<row r="' . $rowNumber . '">' . implode('', $cells) . '</row>';
        $rowNumber++;
    }
    $stringItems = array();
    foreach ($shared as $value) {
        $stringItems[] = '<si><t>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</t></si>';
    }
    $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">' . implode('', $stringItems) . '</sst>';
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . implode('', $sheetRows) . '</sheetData></worksheet>';
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>';
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>';
    $tmp = tempnam(sys_get_temp_dir(), 'smsx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();
    $binary = file_get_contents($tmp);
    unlink($tmp);
    return array('filename' => $filename, 'body' => is_string($binary) ? $binary : '');
}

function sms_import_text_rows($text)
{
    $text = preg_replace('/^\xEF\xBB\xBF/', '', (string) $text);
    $rows = array();
    $handle = fopen('php://temp', 'w+');
    if (!$handle) {
        return $rows;
    }
    fwrite($handle, $text);
    rewind($handle);
    while (($cells = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if (is_array($cells) && count($cells) === 1 && strpos((string) $cells[0], "\t") !== false) {
            $cells = explode("\t", (string) $cells[0]);
        }
        $rows[] = is_array($cells) ? $cells : array();
        if (count($rows) > sms_send_limit() + 1000) {
            break;
        }
    }
    fclose($handle);
    return $rows;
}

function sms_import_xlsx_rows($path)
{
    if (!class_exists('ZipArchive')) {
        return null;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return null;
    }
    foreach (array('xl/sharedStrings.xml', 'xl/worksheets/sheet1.xml') as $part) {
        $stat = $zip->statName($part);
        if (is_array($stat) && (int) $stat['size'] > 41943040) {
            $zip->close();
            return array();
        }
    }
    $shared = array();
    $stringXml = $zip->getFromName('xl/sharedStrings.xml');
    if (is_string($stringXml) && preg_match_all('/<si\b[^>]*>(.*?)<\/si>/s', $stringXml, $items)) {
        foreach ($items[1] as $item) {
            $text = '';
            if (preg_match_all('/<t\b[^>]*>(.*?)<\/t>/s', $item, $texts)) {
                $text = html_entity_decode(implode('', $texts[1]), ENT_QUOTES, 'UTF-8');
            }
            $shared[] = $text;
        }
    }
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!is_string($sheet)) {
        return array();
    }
    $rows = array();
    if (!preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s', $sheet, $rowMatches)) {
        return $rows;
    }
    foreach ($rowMatches[1] as $rowXml) {
        $placed = array();
        if (preg_match_all('/<c\b([^>]*)>(.*?)<\/c>/s', $rowXml, $cellMatches, PREG_SET_ORDER)) {
            $fallback = 0;
            foreach ($cellMatches as $cell) {
                $value = '';
                if (preg_match('/<v>(.*?)<\/v>/s', $cell[2], $raw)) {
                    $value = $raw[1];
                } elseif (preg_match('/<t\b[^>]*>(.*?)<\/t>/s', $cell[2], $raw)) {
                    $value = html_entity_decode($raw[1], ENT_QUOTES, 'UTF-8');
                }
                if (strpos($cell[1], 't="s"') !== false) {
                    $index = (int) $value;
                    $value = isset($shared[$index]) ? $shared[$index] : '';
                }
                $column = $fallback;
                if (preg_match('/\br="([A-Z]+)\d+"/', $cell[1], $ref)) {
                    $column = 0;
                    $letters = $ref[1];
                    $length = strlen($letters);
                    for ($i = 0; $i < $length; $i++) {
                        $column = ($column * 26) + (ord($letters[$i]) - 64);
                    }
                    $column--;
                }
                $placed[$column] = $value;
                $fallback = $column + 1;
            }
        }
        if ($placed) {
            ksort($placed);
            $max = max(array_keys($placed));
            $cells = array();
            for ($i = 0; $i <= $max; $i++) {
                $cells[] = isset($placed[$i]) ? $placed[$i] : '';
            }
        } else {
            $cells = array();
        }
        if ($cells) {
            $rows[] = $cells;
        }
        if (count($rows) > sms_send_limit() + 1000) {
            break;
        }
    }
    return $rows;
}

function sms_import_file($path, $name)
{
    $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
    if ($ext === 'xlsx') {
        $rows = sms_import_xlsx_rows($path);
        if ($rows === null) {
            return array('ok' => false, 'error' => 'This server cannot read Excel files. Save the sheet as CSV and upload that.', 'numbers' => '', 'count' => 0);
        }
    } elseif ($ext === 'csv' || $ext === 'txt') {
        $text = file_get_contents($path);
        $rows = is_string($text) ? sms_import_text_rows($text) : array();
    } else {
        return array('ok' => false, 'error' => 'Upload an Excel .xlsx file or a .csv file. Old .xls sheets need to be saved as .xlsx.', 'numbers' => '', 'count' => 0);
    }
    $numbers = sms_import_lines($rows);
    if ($numbers === '') {
        return array('ok' => false, 'error' => 'No 10-digit Nepal mobile was found. Put numbers in the first sheet, one row each.', 'numbers' => '', 'count' => 0);
    }
    $count = substr_count($numbers, "\n") + 1;
    return array('ok' => true, 'error' => '', 'numbers' => $numbers, 'count' => $count);
}

function sms_store_contacts($contacts)
{
    $lines = array();
    foreach ($contacts as $contact) {
        $name = isset($contact['name']) ? (string) $contact['name'] : '';
        $number = (string) $contact['number'];
        $lines[] = $name === '' ? $number : $name . "\t" . $number;
    }
    return implode("\n", $lines);
}

function sms_contacts_from_stored($raw)
{
    $contacts = array();
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    if (!is_array($lines)) {
        return $contacts;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $name = '';
        $number = $line;
        if (strpos($line, "\t") !== false) {
            $pair = explode("\t", $line, 2);
            $name = sms_contact_name($pair[0]);
            $number = $pair[1];
        }
        $digits = auth_mobile_number($number);
        if (!preg_match('/^9[78]\d{8}$/', $digits) || isset($contacts[$digits])) {
            continue;
        }
        $contacts[$digits] = array('number' => $digits, 'name' => $name);
    }
    return array_values($contacts);
}

function sms_render_messages($template, $contacts)
{
    $messages = array();
    $credits = 0;
    foreach ($contacts as $contact) {
        $text = sms_personalize($template, isset($contact['name']) ? $contact['name'] : '');
        if ($text === '') {
            return array('ok' => false, 'error' => 'The message is empty after the name is added.', 'messages' => array(), 'credits' => 0);
        }
        $parts = sms_message_parts($text);
        $credits += $parts;
        $messages[] = array(
            'number' => $contact['number'],
            'text' => $text,
            'parts' => $parts
        );
    }
    if (!$messages) {
        return array('ok' => false, 'error' => 'Add at least one mobile number.', 'messages' => array(), 'credits' => 0);
    }
    return array('ok' => true, 'error' => '', 'messages' => $messages, 'credits' => $credits);
}
