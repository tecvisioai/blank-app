<?php
require_once __DIR__ . '/helpers.php';

function get_reports() {
    $currentUser = require_auth();
    $pdo = get_db();
    $filters = [];
    $params = [];

    if (!empty($_GET['start_date'])) {
        $filters[] = 'p.start_date >= :start_date';
        $params['start_date'] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $filters[] = 'p.end_date <= :end_date';
        $params['end_date'] = $_GET['end_date'];
    }
    if (!empty($_GET['group'])) {
        $filters[] = 'p.owner_user_id IN (SELECT ug.user_id FROM user_groups ug JOIN groups g ON ug.group_id = g.id WHERE g.name = :group)';
        $params['group'] = $_GET['group'];
    }
    if (!empty($_GET['owner_user_id'])) {
        $filters[] = 'p.owner_user_id = :owner_user_id';
        $params['owner_user_id'] = intval($_GET['owner_user_id']);
    }
    if (!empty($_GET['project_id'])) {
        $filters[] = 'p.id = :project_id';
        $params['project_id'] = intval($_GET['project_id']);
    }
    if (!empty($_GET['status'])) {
        $filters[] = 'p.status = :status';
        $params['status'] = $_GET['status'];
    }
    if (!empty($_GET['priority'])) {
        $filters[] = 'p.priority = :priority';
        $params['priority'] = $_GET['priority'];
    }
    if (!empty($_GET['phase'])) {
        $filters[] = 'p.phase = :phase';
        $params['phase'] = $_GET['phase'];
    }

    $where = 'WHERE 1=1';
    if ($filters) {
        $where .= ' AND ' . implode(' AND ', $filters);
    }

    $reports = [];

    $reports['project_planned_hours'] = fetch_report($pdo, 'SELECT p.id AS project_id, p.name AS project_name, p.status, p.priority, COALESCE(SUM(a.weekly_planned_hours), 0) AS planned_hours FROM projects p LEFT JOIN activities a ON a.project_id = p.id WHERE p.active = 1 ' . ($filters ? 'AND ' . implode(' AND ', $filters) : '') . ' GROUP BY p.id ORDER BY planned_hours DESC', $params);

    $reports['project_actual_hours'] = fetch_report($pdo, 'SELECT p.id AS project_id, p.name AS project_name, COALESCE(SUM(wa.actual_hours), 0) AS actual_hours FROM weekly_actuals wa JOIN activities a ON wa.activity_id = a.id JOIN projects p ON a.project_id = p.id WHERE p.active = 1 ' . ($filters ? 'AND ' . implode(' AND ', $filters) : '') . ' GROUP BY p.id ORDER BY actual_hours DESC', $params);

    $reports['user_actual_hours'] = fetch_report($pdo, 'SELECT u.id AS user_id, u.full_name AS user_name, COALESCE(SUM(wa.actual_hours), 0) AS actual_hours FROM weekly_actuals wa JOIN users u ON wa.user_id = u.id JOIN activities a ON wa.activity_id = a.id JOIN projects p ON a.project_id = p.id WHERE p.active = 1 ' . ($filters ? 'AND ' . implode(' AND ', $filters) : '') . ' GROUP BY u.id ORDER BY actual_hours DESC', $params);

    $reports['status_summary'] = fetch_report($pdo, 'SELECT p.status, COUNT(*) AS projects, COALESCE(SUM(a.weekly_planned_hours),0) AS planned_hours FROM projects p LEFT JOIN activities a ON a.project_id = p.id WHERE p.active = 1 ' . ($filters ? 'AND ' . implode(' AND ', $filters) : '') . ' GROUP BY p.status ORDER BY projects DESC', $params);

    $reports['priority_summary'] = fetch_report($pdo, 'SELECT p.priority, COUNT(*) AS projects, COALESCE(SUM(a.weekly_planned_hours),0) AS planned_hours FROM projects p LEFT JOIN activities a ON a.project_id = p.id WHERE p.active = 1 ' . ($filters ? 'AND ' . implode(' AND ', $filters) : '') . ' GROUP BY p.priority ORDER BY projects DESC', $params);

    $reports['my_projects'] = fetch_report($pdo, 'SELECT p.* FROM projects p WHERE p.active = 1 AND p.owner_user_id = :user_id ' . (!empty($filters) ? 'AND ' . implode(' AND ', $filters) : ''), array_merge($params, ['user_id' => $currentUser['id']]));

    $reports['group_projects'] = fetch_report($pdo, 'SELECT DISTINCT p.* FROM projects p JOIN user_groups ug ON p.owner_user_id = ug.user_id JOIN groups g ON ug.group_id = g.id WHERE p.active = 1 AND g.name IN (' . implode(',', array_fill(0, count($currentUser['groups']), '?')) . ')' . (!empty($filters) ? ' AND ' . implode(' AND ', $filters) : ''), array_merge($params, array_column($currentUser['groups'], 'name')));

    send_json(true, ['reports' => $reports], 'Reports retrieved.');
}

function fetch_report($pdo, $sql, $params) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
