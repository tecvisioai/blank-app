<?php
require_once __DIR__ . '/helpers.php';

function request_password_reset() {
    $body = get_request_body();
    $email = trim($body['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        validation_error('Valid email is required.');
    }

    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = :email AND active = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    $message = 'If the email exists, a reset link has been sent.';
    if (!$user) {
        send_json(true, [], $message);
    }

    $token = bin2hex(random_bytes(24));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)')
        ->execute(['user_id' => $user['id'], 'token_hash' => $tokenHash, 'expires_at' => $expiresAt]);

    $resetUrl = rtrim(FRONTEND_BASE_URL, '/') . '/password-reset/' . $token;
    $bodyText = "Hello {$user['full_name']},\n\nA password reset request was received for your account. Click the link below to reset your password:\n\n$resetUrl\n\nIf you did not request this, please ignore this message. The link expires in 1 hour.\n";
    send_mail($email, 'Password Reset Request', $bodyText);
    send_json(true, [], $message);
}

function reset_password() {
    $body = get_request_body();
    $token = trim($body['token'] ?? '');
    $password = trim($body['password'] ?? '');
    $confirm = trim($body['confirm_password'] ?? '');

    if (!$token || !$password || !$confirm) {
        validation_error('Token, password and confirmation are required.');
    }
    if ($password !== $confirm) {
        validation_error('Passwords do not match.');
    }
    if (strlen($password) < 6) {
        validation_error('Password must be at least 6 characters.');
    }

    $tokenHash = hash('sha256', $token);
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT pr.id, pr.user_id FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token_hash = :token_hash AND pr.used = 0 AND pr.expires_at >= NOW() AND u.active = 1 LIMIT 1');
    $stmt->execute(['token_hash' => $tokenHash]);
    $reset = $stmt->fetch();
    if (!$reset) {
        send_json(false, [], 'Invalid or expired token.', 400);
    }

    $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id')
        ->execute(['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'user_id' => $reset['user_id']]);
    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = :id')->execute(['id' => $reset['id']]);

    send_json(true, [], 'Password has been reset successfully.');
}
