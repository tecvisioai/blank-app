<?php
require_once __DIR__ . '/helpers.php';

function get_lookups() {
    $pdo = get_db();
    $stmt = $pdo->query('SELECT category, value, label FROM lookup_options WHERE active = 1 ORDER BY category, position');
    $rows = $stmt->fetchAll();
    $lookups = [];
    foreach ($rows as $row) {
        $lookups[$row['category']][] = ['value' => $row['value'], 'label' => $row['label']];
    }
    $stmt = $pdo->query('SELECT id, name FROM groups ORDER BY name');
    $groups = $stmt->fetchAll();
    $stmt = $pdo->query('SELECT id, full_name FROM users WHERE active = 1 ORDER BY full_name');
    $users = $stmt->fetchAll();
    send_json(true, ['lookups' => $lookups, 'groups' => $groups, 'users' => $users], 'Lookup data retrieved.');
}
