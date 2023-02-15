ALTER TABLE
	`user_info`
MODIFY
	`usertype` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
MODIFY
	`last_login_time` datetime DEFAULT NULL,
MODIFY
	`last_visit_time` datetime DEFAULT NULL;
