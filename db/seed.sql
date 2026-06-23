USE project_tracker;

INSERT IGNORE INTO groups (name) VALUES ('AAIT'), ('BAT');

INSERT IGNORE INTO lookup_options (category, value, label, position) VALUES
('project_phase','Discovery','Discovery',10),
('project_phase','Design','Design',20),
('project_phase','Development','Development',30),
('project_phase','Testing','Testing',40),
('project_type','Internal','Internal',10),
('project_type','Customer','Customer',20),
('project_type','Compliance','Compliance',30),
('project_status','Planned','Planned',10),
('project_status','Active','Active',20),
('project_status','On Hold','On Hold',30),
('project_status','Completed','Completed',40),
('project_priority','Low','Low',10),
('project_priority','Medium','Medium',20),
('project_priority','High','High',30),
('project_priority','Critical','Critical',40);

INSERT IGNORE INTO users (email, full_name, password_hash, role, active) VALUES
('admin@company.local','System Administrator','$2y$10$u2sPgvVuo8UZ1/5I6d74rui3UWqzJz8tGba7wohdkI6Y6nDQP2bG6','Admin',1),
('manager@company.local','Project Manager','$2y$10$u2sPgvVuo8UZ1/5I6d74rui3UWqzJz8tGba7wohdkI6Y6nDQP2bG6','Manager',1),
('user@company.local','Delivery User','$2y$10$u2sPgvVuo8UZ1/5I6d74rui3UWqzJz8tGba7wohdkI6Y6nDQP2bG6','User',1);

INSERT IGNORE INTO user_groups (user_id, group_id) VALUES
(1,1),
(1,2),
(2,1),
(3,2);

INSERT IGNORE INTO projects (name, phase, type, status, priority, business_team, business_lead, owner_user_id, start_date, end_date) VALUES
('Platform Renewal','Discovery','Internal','Active','High','Business Ops','Angela Reeve',2,'2026-06-01','2026-12-31');

INSERT IGNORE INTO activities (project_id, name, owner_user_id, start_date, end_date, weekly_planned_hours) VALUES
(1,'Discovery Phase','2','2026-06-01','2026-06-30',16.00),
(1,'Design Review','3','2026-07-01','2026-07-31',20.00);

INSERT IGNORE INTO weekly_actuals (user_id, activity_id, week_start_date, actual_hours, notes) VALUES
(3,2,'2026-06-17',18.00,'First week actuals');
