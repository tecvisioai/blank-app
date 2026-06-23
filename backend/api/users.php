<?php
require_once __DIR__ . '/helpers.php';

function route_users($method, $id = null) {
    $currentUser = require_auth();
    switch ($method) {
        case 'GET':
            if ($id) {
                get_user(intval($id), $currentUser);
            } else {
                list_users($currentUser);
            }
            break;
        case 'POST':
            create_user($currentUser);
            break;
        case 'PUT':
        case 'PATCH':
            if (!$id) {
                validation_error('User ID required.');
            }
            update_user(intval($id), $currentUser);
            break;
        case 'DELETE':
            if (!$id) {
                validation_error('User ID required.');
            }
            deactivate_user(intval($id), $currentUser);
            break;
        default:
            send_json(false, [], 'Method not allowed', 405);
    }
}

function list_users($currentUser) {
    if (!user_is_admin($currentUser)) {
        send_json(false, [], 'Access denied', 403);
    }
    $pdo = get_db();
    $params = [];
    $sql = 'SELECT u.id, u.email, u.full_name, u.role, u.active, u.created_at, u.updated_at, GROUP_CONCAT(g.name SEPARATOR ",") AS groups
            FROM users u
            LEFT JOIN user_groups ug ON u.id = ug.user_id
            LEFT JOIN groups g ON ug.group_id = g.id';
    $filters = [];
    if (!empty($_GET['group'])) {
        $filters[] = 'u.id IN (SELECT user_id FROM user_groups ug2 JOIN groups g2 ON ug2.group_id = g2.id WHERE g2.name = :group)';
        $params['group'] = $_GET['group'];
    }
    if (!empty($_GET['role'])) {
        $filters[] = 'u.role = :role';
        $params['role'] = $_GET['role'];
    }
    if (!empty($_GET['active'])) {
        $filters[] = 'u.active = :active';
        $params['active'] = $_GET['active'] ? 1 : 0;
    }
    if ($filters) {
        $sql .= ' WHERE ' . implode(' AND ', $filters);
    }
    $sql .= ' GROUP BY u.id ORDER BY u.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    send_json(true, ['users' => $users], 'User list retrieved.');
}

function get_user($id, $currentUser) {
    if (!user_is_admin($currentUser) && $currentUser['id'] !== $id) {
        send_json(false, [], 'Access denied', 403);
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, email, full_name, role, active, created_at, updated_at FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    if (!$user) {
        send_json(false, [], 'User not found', 404);
    }
    $user['groups'] = get_user_groups($user['id']);
    send_json(true, ['user' => $user], 'User retrieved.');
}

function validate_user_payload($payload, $isUpdate = false) {
    $email = trim($payload['email'] ?? '');
    $fullName = trim($payload['full_name'] ?? '');
    $role = trim($payload['role'] ?? 'User');
    $groups = $payload['groups'] ?? [];
    if (!$fullName) {
        validation_error('Full name is required.');
    }
    if (!$isUpdate && !$email) {
        validation_error('Email is required.');
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        validation_error('Valid email is required.');
    }
    if (!in_array($role, ['Admin', 'Manager', 'User'], true)) {
        validation_error('Invalid role selected.');
    }
    if (!is_array($groups) || empty($groups)) {
        validation_error('At least one group is required.');
    }
    return [$email, $fullName, $role, $groups, $payload['password'] ?? null, $payload['active'] ?? 1];
}

function create_user($currentUser) {
    if (!user_is_admin($currentUser)) {
        send_json(false, [], 'Access denied', 403);
    }
    $body = get_request_body();
    [$email, $fullName, $role, $groups, $password, $active] = validate_user_payload($body);
    if (!$password || strlen($password) < 6) {
        validation_error('Password must be at least 6 characters.');
    }

    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        validation_error('Email already exists.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, full_name, password_hash, role, active) VALUES (:email, :full_name, :password_hash, :role, :active)');
    $stmt->execute(['email' => $email, 'full_name' => $fullName, 'password_hash' => $passwordHash, 'role' => $role, 'active' => $active ? 1 : 0]);
    $userId = $pdo->lastInsertId();
    save_user_groups($userId, $groups);
    send_json(true, ['user_id' => $userId], 'User created.');
}

function update_user($id, $currentUser) {
    if (!user_is_admin($currentUser)) {
        send_json(false, [], 'Access denied', 403);
    }
    $body = get_request_body();
    [$email, $fullName, $role, $groups, $password, $active] = validate_user_payload($body, true);
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        send_json(false, [], 'User not found.', 404);
    }
    if ($email) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ($stmt->fetch()) {
            validation_error('Email already assigned to another user.');
        }
    }
    $fields = [];
    $params = ['id' => $id];
    if ($email) {
        $fields[] = 'email = :email';
        $params['email'] = $email;
    }
    if ($fullName) {
        $fields[] = 'full_name = :full_name';
        $params['full_name'] = $fullName;
    }
    if ($role) {
        $fields[] = 'role = :role';
        $params['role'] = $role;
    }
    $fields[] = 'active = :active';
    $params['active'] = $active ? 1 : 0;
    if ($password) {
        if (strlen($password) < 6) {
            validation_error('Password must be at least 6 characters.');
        }
        $fields[] = 'password_hash = :password_hash';
        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }
    if ($fields) {
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    if (is_array($groups) && !empty($groups)) {
        save_user_groups($id, $groups);
    }
    send_json(true, ['user_id' => $id], 'User updated.');
}

function deactivate_user($id, $currentUser) {
    if (!user_is_admin($currentUser)) {
        send_json(false, [], 'Access denied', 403);
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('UPDATE users SET active = 0 WHERE id = :id');
    $stmt->execute(['id' => $id]);
    send_json(true, ['user_id' => $id], 'User deactivated.');
}

function save_user_groups($userId, $groups) {
    $pdo = get_db();
    $pdo->prepare('DELETE FROM user_groups WHERE user_id = :user_id')->execute(['user_id' => $userId]);
    $stmt = $pdo->prepare('SELECT id, name FROM groups WHERE name IN (' . implode(',', array_fill(0, count($groups), '?')) . ')');
    $stmt->execute($groups);
    $existing = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $insert = $pdo->prepare('INSERT INTO user_groups (user_id, group_id) VALUES (:user_id, :group_id)');
    foreach ($groups as $groupName) {
        if (!isset($existing[$groupName])) {
            continue;
        }
        $insert->execute(['user_id' => $userId, 'group_id' => $existing[$groupName]]);
    }
}
