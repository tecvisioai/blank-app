<?php
require_once __DIR__ . '/helpers.php';

function login() {
    $body = get_request_body();
    $email = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';
    if (!$email || !$password) {
        validation_error('Email and password are required.');
    }

    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, email, full_name, password_hash, role, active FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !$user['active'] || !password_verify($password, $user['password_hash'])) {
        send_json(false, [], 'Invalid email or password.', 401);
    }

    $token = generate_jwt(['user_id' => $user['id'], 'role' => $user['role']]);
    unset($user['password_hash']);
    $user['groups'] = get_user_groups($user['id']);
    send_json(true, ['token' => $token, 'user' => $user], 'Login successful.');
}

function logout() {
    require_auth();
    send_json(true, [], 'Logout successful.');
}

function me() {
    $user = require_auth();
    send_json(true, ['user' => $user], 'User profile.');
}
