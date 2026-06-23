<?php
require_once __DIR__ . '/database.php';

function send_json($success, $data = [], $message = '', $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    send_json(true, [], 'OK', 204);
}

function get_request_body() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }
    $parsed = json_decode($input, true);
    return is_array($parsed) ? $parsed : [];
}

function get_authorization_header() {
    $headers = [];
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers[] = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strtolower($name) === 'authorization') {
                $headers[] = trim($value);
            }
        }
    }
    return count($headers) > 0 ? $headers[0] : null;
}

function generate_jwt($payload) {
    $header = base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['exp'] = time() + JWT_EXPIRE;
    $payload['iat'] = time();
    $body = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header.$body", JWT_SECRET, true);
    return "$header.$body." . base64UrlEncode($signature);
}

function validate_jwt($token) {
    if (!$token) {
        return null;
    }
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$header, $body, $signature] = $parts;
    $payloadJson = base64UrlDecode($body);
    if (!$payloadJson) {
        return null;
    }
    $payload = json_decode($payloadJson, true);
    if (!$payload || !isset($payload['exp'])) {
        return null;
    }
    $expectedSig = base64UrlEncode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    if (!hash_equals($expectedSig, $signature)) {
        return null;
    }
    if ($payload['exp'] < time()) {
        return null;
    }
    return $payload;
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    $padding = 4 - (strlen($data) % 4);
    if ($padding !== 4) {
        $data .= str_repeat('=', $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function send_mail($to, $subject, $body) {
    $headers = 'From: ' . MAIL_FROM . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
    $sent = false;
    if (function_exists('mail')) {
        $sent = mail($to, $subject, $body, $headers);
    }
    if (!$sent) {
        error_log("[EMAIL] To: $to Subject: $subject Body: $body\n", 3, EMAIL_LOG_PATH);
    }
    return $sent;
}

function build_in_clause(array $values) {
    if (count($values) === 0) {
        return 'NULL';
    }
    return implode(',', array_fill(0, count($values), '?'));
}

function get_current_user() {
    static $currentUser = null;
    if ($currentUser !== null) {
        return $currentUser;
    }
    $authHeader = get_authorization_header();
    if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
        return null;
    }
    $token = trim(str_ireplace('Bearer ', '', $authHeader));
    $claims = validate_jwt($token);
    if (!$claims || !isset($claims['user_id'])) {
        return null;
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, email, full_name, role, active FROM users WHERE id = :id AND active = 1');
    $stmt->execute(['id' => $claims['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        return null;
    }
    $user['groups'] = get_user_groups($user['id']);
    $currentUser = $user;
    return $currentUser;
}

function require_auth() {
    $user = get_current_user();
    if (!$user) {
        send_json(false, [], 'Authentication required', 401);
    }
    return $user;
}

function user_is_admin($user) {
    return isset($user['role']) && $user['role'] === 'Admin';
}

function get_user_groups($userId) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT g.id, g.name FROM groups g JOIN user_groups ug ON g.id = ug.group_id WHERE ug.user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function filter_params($array, $allowed = []) {
    return array_filter($array, function ($key) use ($allowed) {
        return in_array($key, $allowed, true);
    }, ARRAY_FILTER_USE_KEY);
}

function validation_error($message) {
    send_json(false, [], $message, 422);
}
