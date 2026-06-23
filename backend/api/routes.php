<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/projects.php';
require_once __DIR__ . '/activities.php';
require_once __DIR__ . '/weekly_actuals.php';
require_once __DIR__ . '/lookups.php';
require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/password_resets.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $path);
$path = trim($path, '/');
$segments = explode('/', $path);

if ($segments[0] === 'api') {
    $resource = $segments[1] ?? null;
    $id = $segments[2] ?? null;
    switch ($resource) {
        case 'login':
            if ($method === 'POST') {
                login();
            }
            break;
        case 'logout':
            if ($method === 'POST') {
                logout();
            }
            break;
        case 'me':
            if ($method === 'GET') {
                me();
            }
            break;
        case 'users':
            route_users($method, $id);
            break;
        case 'projects':
            route_projects($method, $id);
            break;
        case 'activities':
            route_activities($method, $id);
            break;
        case 'weekly-actuals':
            route_weekly_actuals($method, $id);
            break;
        case 'lookups':
            if ($method === 'GET') {
                get_lookups();
            }
            break;
        case 'reports':
            if ($method === 'GET') {
                get_reports();
            }
            break;
        case 'password-reset-request':
            if ($method === 'POST') {
                request_password_reset();
            }
            break;
        case 'password-reset':
            if ($method === 'POST') {
                reset_password();
            }
            break;
        default:
            send_json(false, [], 'Endpoint not found', 404);
    }
}

send_json(false, [], 'Route not found', 404);
