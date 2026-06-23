<?php
require_once __DIR__ . '/helpers.php';

function route_projects($method, $id = null) {
    $currentUser = require_auth();
    switch ($method) {
        case 'GET':
            if ($id) {
                get_project(intval($id), $currentUser);
            } else {
                list_projects($currentUser);
            }
            break;
        case 'POST':
            create_project($currentUser);
            break;
        case 'PUT':
        case 'PATCH':
            if (!$id) {
                validation_error('Project ID required.');
            }
            update_project(intval($id), $currentUser);
            break;
        case 'DELETE':
            if (!$id) {
                validation_error('Project ID required.');
            }
            delete_project(intval($id), $currentUser);
            break;
        default:
            send_json(false, [], 'Method not allowed', 405);
    }
}

function list_projects($currentUser) {
    $pdo = get_db();
    $ownedGroups = array_column($currentUser['groups'], 'name');
    $groupPlaceholders = build_in_clause($ownedGroups);
    $query = 'SELECT p.*, u.full_name AS owner_name, COALESCE(SUM(a.weekly_planned_hours), 0) AS total_planned_hours
        FROM projects p
        JOIN users u ON p.owner_user_id = u.id
        LEFT JOIN activities a ON a.project_id = p.id AND a.active = 1
        WHERE p.active = 1 AND (
            p.owner_user_id = :owner_id'
            . ($groupPlaceholders !== 'NULL' ? ' OR p.owner_user_id IN (SELECT ug.user_id FROM user_groups ug JOIN groups g ON ug.group_id = g.id WHERE g.name IN (' . $groupPlaceholders . '))' : '')
            . ($groupPlaceholders !== 'NULL' ? ' OR p.id IN (SELECT a2.project_id FROM activities a2 JOIN user_groups ug2 ON a2.owner_user_id = ug2.user_id JOIN groups g2 ON ug2.group_id = g2.id WHERE g2.name IN (' . $groupPlaceholders . '))' : '')
        . ')';
    $params = [$currentUser['id']];
    if ($groupPlaceholders !== 'NULL') {
        $params = array_merge($params, $ownedGroups, $ownedGroups);
    }
    $filters = [];
    if (!empty($_GET['status'])) {
        $filters[] = 'p.status = :status';
        $params['status'] = $_GET['status'];
    }
    if (!empty($_GET['priority'])) {
        $filters[] = 'p.priority = :priority';
        $params['priority'] = $_GET['priority'];
    }
    if (!empty($_GET['owner_user_id'])) {
        $filters[] = 'p.owner_user_id = :owner_user_id_filter';
        $params['owner_user_id_filter'] = intval($_GET['owner_user_id']);
    }
    if (!empty($_GET['search'])) {
        $filters[] = '(p.name LIKE :search OR p.business_team LIKE :search OR p.business_lead LIKE :search)';
        $params['search'] = '%' . $_GET['search'] . '%';
    }
    if ($filters) {
        $query .= ' AND ' . implode(' AND ', $filters);
    }
    $query .= ' GROUP BY p.id ORDER BY p.start_date DESC';

    $countQuery = 'SELECT COUNT(DISTINCT p.id) FROM projects p WHERE p.active = 1 AND (
            p.owner_user_id = :owner_id'
            . ($groupPlaceholders !== 'NULL' ? ' OR p.owner_user_id IN (SELECT ug.user_id FROM user_groups ug JOIN groups g ON ug.group_id = g.id WHERE g.name IN (' . $groupPlaceholders . '))' : '')
            . ($groupPlaceholders !== 'NULL' ? ' OR p.id IN (SELECT a2.project_id FROM activities a2 JOIN user_groups ug2 ON a2.owner_user_id = ug2.user_id JOIN groups g2 ON ug2.group_id = g2.id WHERE g2.name IN (' . $groupPlaceholders . '))' : '')
        . ')';
    if ($filters) {
        $countQuery .= ' AND ' . implode(' AND ', $filters);
    }

    $page = max(1, intval($_GET['page'] ?? 1));
    $pageSize = max(5, min(50, intval($_GET['page_size'] ?? 10)));
    $offset = ($page - 1) * $pageSize;

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = intval($countStmt->fetchColumn());

    $query .= ' LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        if (is_int($key)) {
            $stmt->bindValue($key + 1, $value, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    send_json(true, ['projects' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'page_size' => $pageSize], 'Projects loaded.');
}

function get_project($projectId, $currentUser) {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT p.*, u.full_name AS owner_name FROM projects p JOIN users u ON p.owner_user_id = u.id WHERE p.id = :id AND p.active = 1');
    $stmt->execute(['id' => $projectId]);
    $project = $stmt->fetch();
    if (!$project) {
        send_json(false, [], 'Project not found.', 404);
    }
    if (!user_can_view_project($projectId, $currentUser)) {
        send_json(false, [], 'Access denied.', 403);
    }
    $stmt = $pdo->prepare('SELECT a.*, u.full_name AS owner_name FROM activities a JOIN users u ON a.owner_user_id = u.id WHERE a.project_id = :project_id AND a.active = 1 ORDER BY a.start_date ASC');
    $stmt->execute(['project_id' => $projectId]);
    $project['activities'] = $stmt->fetchAll();
    send_json(true, ['project' => $project], 'Project details loaded.');
}

function validate_project_payload($payload) {
    $name = trim($payload['name'] ?? '');
    $phase = trim($payload['phase'] ?? '');
    $type = trim($payload['type'] ?? '');
    $status = trim($payload['status'] ?? '');
    $priority = trim($payload['priority'] ?? '');
    $ownerId = intval($payload['owner_user_id'] ?? 0);
    if (!$name) {
        validation_error('Project name is required.');
    }
    if (!$phase || !$type || !$status || !$priority || !$ownerId) {
        validation_error('Phase, type, status, priority, and owner are required.');
    }
    return [$name, $phase, $type, $status, $priority, trim($payload['business_team'] ?? ''), trim($payload['business_lead'] ?? ''), $ownerId, $payload['start_date'] ?? null, $payload['end_date'] ?? null];
}

function create_project($currentUser) {
    $body = get_request_body();
    [$name, $phase, $type, $status, $priority, $businessTeam, $businessLead, $ownerId, $startDate, $endDate] = validate_project_payload($body);
    if (!project_owner_visible($ownerId, $currentUser)) {
        validation_error('Selected owner must be visible within your groups.');
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO projects (name, phase, type, status, priority, business_team, business_lead, owner_user_id, start_date, end_date) VALUES (:name, :phase, :type, :status, :priority, :business_team, :business_lead, :owner_user_id, :start_date, :end_date)');
    $stmt->execute(['name' => $name, 'phase' => $phase, 'type' => $type, 'status' => $status, 'priority' => $priority, 'business_team' => $businessTeam, 'business_lead' => $businessLead, 'owner_user_id' => $ownerId, 'start_date' => $startDate, 'end_date' => $endDate]);
    send_json(true, ['project_id' => get_db()->lastInsertId()], 'Project created.');
}

function update_project($projectId, $currentUser) {
    $body = get_request_body();
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT owner_user_id FROM projects WHERE id = :id AND active = 1');
    $stmt->execute(['id' => $projectId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        send_json(false, [], 'Project not found.', 404);
    }
    if (!user_can_view_project($projectId, $currentUser)) {
        send_json(false, [], 'Access denied.', 403);
    }
    [$name, $phase, $type, $status, $priority, $businessTeam, $businessLead, $ownerId, $startDate, $endDate] = validate_project_payload($body);
    $stmt = $pdo->prepare('UPDATE projects SET name = :name, phase = :phase, type = :type, status = :status, priority = :priority, business_team = :business_team, business_lead = :business_lead, owner_user_id = :owner_user_id, start_date = :start_date, end_date = :end_date WHERE id = :id');
    $stmt->execute(['name' => $name, 'phase' => $phase, 'type' => $type, 'status' => $status, 'priority' => $priority, 'business_team' => $businessTeam, 'business_lead' => $businessLead, 'owner_user_id' => $ownerId, 'start_date' => $startDate, 'end_date' => $endDate, 'id' => $projectId]);
    send_json(true, ['project_id' => $projectId], 'Project updated.');
}

function delete_project($projectId, $currentUser) {
    $pdo = get_db();
    if (!user_can_view_project($projectId, $currentUser)) {
        send_json(false, [], 'Access denied.', 403);
    }
    $stmt = $pdo->prepare('UPDATE projects SET active = 0 WHERE id = :id');
    $stmt->execute(['id' => $projectId]);
    send_json(true, ['project_id' => $projectId], 'Project deactivated.');
}

function user_can_view_project($projectId, $currentUser) {
    $pdo = get_db();
    $ownedGroups = array_column($currentUser['groups'], 'name');
    $groupPlaceholders = build_in_clause($ownedGroups);
    $sql = 'SELECT COUNT(*) FROM projects p
        WHERE p.id = :project_id AND p.active = 1 AND (
            p.owner_user_id = :user_id'
            . ($groupPlaceholders !== 'NULL' ? ' OR p.owner_user_id IN (SELECT ug.user_id FROM user_groups ug JOIN groups g ON ug.group_id = g.id WHERE g.name IN (' . $groupPlaceholders . '))' : '')
            . ($groupPlaceholders !== 'NULL' ? ' OR p.id IN (SELECT a.project_id FROM activities a JOIN user_groups ug2 ON a.owner_user_id = ug2.user_id JOIN groups g2 ON ug2.group_id = g2.id WHERE g2.name IN (' . $groupPlaceholders . '))' : '')
        . ')';
    $params = ['project_id' => $projectId, 'user_id' => $currentUser['id']];
    if ($groupPlaceholders !== 'NULL') {
        $params = array_merge($params, $ownedGroups, $ownedGroups);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

function project_owner_visible($ownerUserId, $currentUser) {
    if ($currentUser['id'] === $ownerUserId) {
        return true;
    }
    $ownedGroups = array_column($currentUser['groups'], 'name');
    if (empty($ownedGroups)) {
        return false;
    }
    $groupPlaceholders = build_in_clause($ownedGroups);
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_groups ug JOIN groups g ON ug.group_id = g.id WHERE ug.user_id = :owner_user_id AND g.name IN (' . $groupPlaceholders . ')');
    $params = array_merge(['owner_user_id' => $ownerUserId], $ownedGroups);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}
