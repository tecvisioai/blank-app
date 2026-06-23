<?php
require_once __DIR__ . '/helpers.php';

function route_weekly_actuals($method, $id = null) {
    $currentUser = require_auth();
    switch ($method) {
        case 'GET':
            get_weekly_actuals($currentUser);
            break;
        case 'POST':
            upsert_weekly_actuals($currentUser);
            break;
        case 'PUT':
        case 'PATCH':
            if (!$id) {
                validation_error('Record ID required.');
            }
            update_weekly_actual(intval($id), $currentUser);
            break;
        case 'DELETE':
            if (!$id) {
                validation_error('Record ID required.');
            }
            delete_weekly_actual(intval($id), $currentUser);
            break;
        default:
            send_json(false, [], 'Method not allowed', 405);
    }
}

function get_weekly_actuals($currentUser) {
    $pdo = get_db();
    $weekStart = $_GET['week_start_date'] ?? null;
    if (!$weekStart) {
        validation_error('Week start date is required.');
    }
    $sql = 'SELECT a.id AS activity_id, p.name AS project_name, a.name AS activity_name, a.weekly_planned_hours, wa.id AS record_id, wa.actual_hours, wa.notes, wa.week_start_date
        FROM activities a
        JOIN projects p ON a.project_id = p.id
        LEFT JOIN weekly_actuals wa ON wa.activity_id = a.id AND wa.week_start_date = :week_start_date AND wa.user_id = :user_id
        WHERE a.owner_user_id = :user_id AND a.active = 1 AND p.active = 1
        ORDER BY a.start_date ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $currentUser['id'], 'week_start_date' => $weekStart]);
    send_json(true, ['weekly_actuals' => $stmt->fetchAll()], 'Weekly actuals retrieved.');
}

function upsert_weekly_actuals($currentUser) {
    $body = get_request_body();
    $records = $body['records'] ?? [];
    if (!is_array($records) || empty($records)) {
        validation_error('Records are required.');
    }
    $pdo = get_db();
    $insert = $pdo->prepare('INSERT INTO weekly_actuals (user_id, activity_id, week_start_date, actual_hours, notes) VALUES (:user_id, :activity_id, :week_start_date, :actual_hours, :notes)
        ON DUPLICATE KEY UPDATE actual_hours = VALUES(actual_hours), notes = VALUES(notes), updated_at = CURRENT_TIMESTAMP');
    foreach ($records as $record) {
        $activityId = intval($record['activity_id'] ?? 0);
        $weekStartDate = $record['week_start_date'] ?? null;
        $actualHours = floatval($record['actual_hours'] ?? 0);
        $notes = trim($record['notes'] ?? '');
        if (!$activityId || !$weekStartDate) {
            continue;
        }
        $insert->execute(['user_id' => $currentUser['id'], 'activity_id' => $activityId, 'week_start_date' => $weekStartDate, 'actual_hours' => $actualHours, 'notes' => $notes]);
    }
    send_json(true, [], 'Weekly actuals saved.');
}

function update_weekly_actual($id, $currentUser) {
    $body = get_request_body();
    $actualHours = isset($body['actual_hours']) ? floatval($body['actual_hours']) : null;
    if ($actualHours === null) {
        validation_error('Actual hours are required.');
    }
    $notes = trim($body['notes'] ?? '');
    $pdo = get_db();
    $stmt = $pdo->prepare('UPDATE weekly_actuals SET actual_hours = :actual_hours, notes = :notes WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['actual_hours' => $actualHours, 'notes' => $notes, 'id' => $id, 'user_id' => $currentUser['id']]);
    send_json(true, ['id' => $id], 'Weekly actual updated.');
}

function delete_weekly_actual($id, $currentUser) {
    $pdo = get_db();
    $stmt = $pdo->prepare('DELETE FROM weekly_actuals WHERE id = :id AND user_id = :user_id');
    $stmt->execute(['id' => $id, 'user_id' => $currentUser['id']]);
    send_json(true, ['id' => $id], 'Weekly actual deleted.');
}
