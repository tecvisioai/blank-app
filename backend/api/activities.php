<?php
require_once __DIR__ . '/helpers.php';

function route_activities($method, $id = null) {
    $currentUser = require_auth();
    switch ($method) {
        case 'GET':
            if ($id) {
                get_activity(intval($id), $currentUser);
            } else {
                list_activities($currentUser);
            }
            break;
        case 'POST':
            create_activity($currentUser);
            break;
        case 'PUT':
        case 'PATCH':
            if (!$id) {
                validation_error('Activity ID required.');
            }
            update_activity(intval($id), $currentUser);
            break;
        case 'DELETE':
            if (!$id) {
                validation_error('Activity ID required.');
            }
            delete_activity(intval($id), $currentUser);
            break;
        default:
            send_json(false, [], 'Method not allowed', 405);
    }
}

function list_activities($currentUser) {
    $pdo = get_db();
    $ownedGroups = array_column($currentUser['groups'], 'name');
    $groupPlaceholders = build_in_clause($ownedGroups);
    $query = 'SELECT a.*, p.name AS project_name, u.full_name AS owner_name
        FROM activities a
        JOIN projects p ON a.project_id = p.id
        JOIN users u ON a.owner_user_id = u.id
        WHERE a.active = 1 AND p.active = 1 AND (
            p.owner_user_id = :user_id'
            . ($groupPlaceholders !== 'NULL' ? ' OR p.owner_user_id IN (SELECT ug.user_id FROM user_groups ug JOIN groups g ON ug.group_id = g.id WHERE g.name IN (' . $groupPlaceholders . '))' : '')
            . ($groupPlaceholders !== 'NULL' ? ' OR a.owner_user_id IN (SELECT ug2.user_id FROM user_groups ug2 JOIN groups g2 ON ug2.group_id = g2.id WHERE g2.name IN (' . $groupPlaceholders . '))' : '')
        . ')';
    $params = [$currentUser['id']];
    if ($groupPlaceholders !== 'NULL') {
        $params = array_merge($params, $ownedGroups, $ownedGroups);
    }
    $filters = [];
    if (!empty($_GET['project_id'])) {
        $filters[] = 'a.project_id = :project_id';
        $params['project_id'] = intval($_GET['project_id']);
    }
    if (!empty($_GET['owner_user_id'])) {
        $filters[] = 'a.owner_user_id = :owner_user_id';
        $params['owner_user_id'] = intval($_GET['owner_user_id']);
    }
    if ($filters) {
        $query .= ' AND ' . implode(' AND ', $filters);
    }
    $query .= ' ORDER BY a.start_date ASC';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    send_json(true, ['activities' => $stmt->fetchAll()], 'Activities loaded.');
}

function get_activity($activityId, $currentUser) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT a.*, p.name AS project_name FROM activities a JOIN projects p ON a.project_id = p.id WHERE a.id = :id AND a.active = 1');
    $stmt->execute(['id' => $activityId]);
    $activity = $stmt->fetch();
    if (!$activity) {
        send_json(false, [], 'Activity not found.', 404);
    }
    if (!user_can_view_project($activity['project_id'], $currentUser)) {
        send_json(false, [], 'Access denied.', 403);
    }
    send_json(true, ['activity' => $activity], 'Activity loaded.');
}

function validate_activity_payload($payload) {
    $projectId = intval($payload['project_id'] ?? 0);
    $name = trim($payload['name'] ?? '');
    $ownerId = intval($payload['owner_user_id'] ?? 0);
    $weeklyPlannedHours = floatval($payload['weekly_planned_hours'] ?? 0);
    if (!$projectId || !$name || !$ownerId) {
        validation_error('Project, activity name, and owner are required.');
    }
    if ($weeklyPlannedHours < 0) {
        validation_error('Weekly planned hours must be zero or greater.');
    }
    return [$projectId, $name, $ownerId, $payload['start_date'] ?? null, $payload['end_date'] ?? null, $weeklyPlannedHours];
}

function create_activity($currentUser) {
    $body = get_request_body();
    [$projectId, $name, $ownerId, $startDate, $endDate, $weeklyPlannedHours] = validate_activity_payload($body);
    if (!user_can_view_project($projectId, $currentUser)) {
        validation_error('Project not visible or not accessible.');
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO activities (project_id, name, owner_user_id, start_date, end_date, weekly_planned_hours) VALUES (:project_id, :name, :owner_user_id, :start_date, :end_date, :weekly_planned_hours)');
    $stmt->execute(['project_id' => $projectId, 'name' => $name, 'owner_user_id' => $ownerId, 'start_date' => $startDate, 'end_date' => $endDate, 'weekly_planned_hours' => $weeklyPlannedHours]);
    send_json(true, ['activity_id' => $pdo->lastInsertId()], 'Activity created.');
}

function update_activity($activityId, $currentUser) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM activities WHERE id = :id AND active = 1');
    $stmt->execute(['id' => $activityId]);
    $activity = $stmt->fetch();
    if (!$activity) {
        send_json(false, [], 'Activity not found.', 404);
    }
    if (!user_can_view_project($activity['project_id'], $currentUser)) {
        send_json(false, [], 'Access denied.', 403);
    }
    $body = get_request_body();
    [$projectId, $name, $ownerId, $startDate, $endDate, $weeklyPlannedHours] = validate_activity_payload($body);
    $stmt = $pdo->prepare('UPDATE activities SET project_id = :project_id, name = :name, owner_user_id = :owner_user_id, start_date = :start_date, end_date = :end_date, weekly_planned_hours = :weekly_planned_hours WHERE id = :id');
    $stmt->execute(['project_id' => $projectId, 'name' => $name, 'owner_user_id' => $ownerId, 'start_date' => $startDate, 'end_date' => $endDate, 'weekly_planned_hours' => $weeklyPlannedHours, 'id' => $activityId]);
    send_json(true, ['activity_id' => $activityId], 'Activity updated.');
}

function delete_activity($activityId, $currentUser) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT project_id FROM activities WHERE id = :id');
    $stmt->execute(['id' => $activityId]);
    $activity = $stmt->fetch();
    if (!$activity) {
        send_json(false, [], 'Activity not found.', 404);
    }
    if (!user_can_view_project($activity['project_id'], $currentUser)) {
        send_json(false, [], 'Access denied.', 403);
    }
    $stmt = $pdo->prepare('UPDATE activities SET active = 0 WHERE id = :id');
    $stmt->execute(['id' => $activityId]);
    send_json(true, ['activity_id' => $activityId], 'Activity deactivated.');
}

function user_can_view_project($projectId, $currentUser) {
    require_once __DIR__ . '/projects.php';
    return 
        isset($currentUser) && 
        function_exists('user_can_view_project') && 
        user_can_view_project($projectId, $currentUser);
}
