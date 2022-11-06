-- InnoDB
ALTER TABLE `best_ac_submissions` ENGINE=InnoDB TABLESPACE `innodb_system`;
ALTER TABLE `judger_info` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `problems_contents` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `submissions` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `user_info` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New Tables

CREATE TABLE `meta` (
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `updated_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `submissions_history` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `submission_id` int UNSIGNED NOT NULL,
  `judge_reason` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `judge_time` datetime DEFAULT NULL,
  `judger` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result` mediumblob NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_details` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `result_error` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` int DEFAULT NULL,
  `used_time` int NOT NULL DEFAULT '0',
  `used_memory` int NOT NULL DEFAULT '0',
  `major` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `submission_judge_time` (`submission_id`,`judge_time`,`id`),
  KEY `submission` (`submission_id`,`id`),
  KEY `status_major` (`status`,`major`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_updates` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` int UNSIGNED NOT NULL,
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type_id_time` (`type`,`target_id`,`time`),
  KEY `type_time` (`type`,`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing Tables

ALTER TABLE `blogs`
  ADD `active_time` datetime NOT NULL AFTER `post_time`;
ALTER TABLE `blogs`
  MODIFY `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `content_md` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `zan` int NOT NULL DEFAULT '0';
ALTER TABLE `blogs`
  ADD KEY `post_time` (`post_time`),
  ADD KEY `active_time` (`active_time`),
  ADD KEY `poster` (`poster`,`is_hidden`);

ALTER TABLE `blogs_comments`
  ADD `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  ADD `reason_to_hide` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `blogs_comments`
  ADD UNIQUE KEY `reply_id` (`reply_id`,`id`),
  ADD KEY `blog_id` (`blog_id`,`post_time`),
  ADD KEY `blog_id_2` (`blog_id`,`reply_id`);

ALTER TABLE `contests`
  ADD `end_time` datetime GENERATED ALWAYS AS ((`start_time` + interval `last_min` minute)) VIRTUAL NOT NULL AFTER `start_time`;
ALTER TABLE `contests`
  MODIFY `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `player_num` int NOT NULL DEFAULT '0',
  MODIFY `extra_config` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '{}',
  MODIFY `zan` int NOT NULL DEFAULT '0';

ALTER TABLE `contests_asks`
  ADD KEY `contest_id` (`contest_id`,`is_hidden`,`username`) USING BTREE,
  ADD KEY `username` (`username`,`contest_id`) USING BTREE;

ALTER TABLE `contests_problems`
  CHANGE `dfn` `level` int NOT NULL DEFAULT 0;
ALTER TABLE `contests_problems`
  ADD KEY `contest_id` (`contest_id`,`problem_id`);

ALTER TABLE `contests_registrants`
  CHANGE `rank` `final_rank` int NOT NULL;

ALTER TABLE `contests_submissions`
  ADD `cnt` int DEFAULT NULL,
  ADD `n_failures` int DEFAULT NULL;

ALTER TABLE `contests_reviews`
  ADD KEY `contest_id` (`contest_id`,`problem_id`),
  ADD KEY `poster` (`poster`);

ALTER TABLE `custom_test_submissions`
  ADD KEY `submitter` (`submitter`,`problem_id`,`id`),
  ADD KEY `judge_time` (`judge_time`,`id`);

ALTER TABLE `groups_assignments`
  ADD KEY `list_id` (`list_id`,`group_id`);

ALTER TABLE `groups_users`
  ADD KEY `group_id` (`group_id`, `username`),
  ADD KEY `username` (`username`, `group_id`);

ALTER TABLE `hacks`
  ADD `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL AFTER `success`;
ALTER TABLE `hacks`
  MODIFY `hacker` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `owner` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `input` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `input_type` char(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `hacks`
  ADD KEY `status` (`status`),
  ADD KEY `judge_time` (`judge_time`);

ALTER TABLE `judger_info`
  ADD `enabled` tinyint(1) NOT NULL DEFAULT '1',
  ADD `display_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  ADD `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `judger_info`
  MODIFY `judger_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `password` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `ip` char(20) COLLATE utf8mb4_unicode_ci NOT NULL;

ALTER TABLE `problems`
  ADD `assigned_to_judger` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'any';
ALTER TABLE `problems`
  MODIFY `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `submission_requirement` mediumtext COLLATE utf8mb4_unicode_ci,
  MODIFY `extra_config` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '{"view_content_type":"ALL","view_details_type":"ALL"}';
ALTER TABLE `problems`
  ADD KEY `assigned_to_judger` (`assigned_to_judger`);

ALTER TABLE `problems_contents`
  MODIFY `statement` longtext COLLATE utf8mb4_unicode_ci,
  MODIFY `statement_md` longtext COLLATE utf8mb4_unicode_ci;

ALTER TABLE `problems_permissions` ADD KEY `problem_id` (`problem_id`);

ALTER TABLE `problems_solutions` ADD KEY `problem_id` (`problem_id`);

ALTER TABLE `lists_problems` ADD KEY `list_id` (`list_id`);

ALTER TABLE `search_requests`
  MODIFY `remote_addr` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `type` enum('search','autocomplete') COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `q` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `result` json NOT NULL;

ALTER TABLE `submissions`
  ADD `judge_reason` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER `tot_size`,
  ADD `judger` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER `judge_time`,
  ADD `hide_score_to_others` tinyint(1) NOT NULL DEFAULT '0' AFTER `score`,
  ADD `hidden_score` int DEFAULT NULL AFTER `hide_score_to_others`;
ALTER TABLE `submissions`
  MODIFY `submitter` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `content` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `language` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `tot_size` int NOT NULL,
  MODIFY `result` mediumblob NOT NULL,
  MODIFY `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `result_error` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  MODIFY `status_details` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `submissions`
  DROP KEY `is_hidden`,
  DROP KEY `score`,
  ADD KEY `status` (`status`,`id`),
  ADD KEY `result_error` (`result_error`),
  ADD KEY `problem_id` (`problem_id`,`id`),
  ADD KEY `language` (`language`,`id`),
  ADD KEY `language2` (`is_hidden`,`language`,`id`),
  ADD KEY `user_score` (`problem_id`,`submitter`,`score`,`id`),
  ADD KEY `problem_id2` (`is_hidden`,`problem_id`,`id`),
  ADD KEY `id2` (`is_hidden`,`id`),
  ADD KEY `problem_score2` (`is_hidden`,`problem_id`,`score`,`id`),
  ADD KEY `contest_submission_status` (`contest_id`,`status`),
  ADD KEY `submitter2` (`is_hidden`,`submitter`,`id`),
  ADD KEY `submitter` (`submitter`,`id`) USING BTREE,
  ADD KEY `contest_id` (`contest_id`,`is_hidden`) USING BTREE;

ALTER TABLE `user_info`
  MODIFY `usergroup` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'U',
  MODIFY `email` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `password` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `svn_password` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  MODIFY `qq` bigint NOT NULL DEFAULT '0',
  MODIFY `sex` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'U',
  MODIFY `ac_num` int NOT NULL DEFAULT 0,
  MODIFY `remote_addr` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  MODIFY `http_x_forwarded_for` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  MODIFY `remember_token` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  MODIFY `motto` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user_info`
  CHANGE `last_login` `last_login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  CHANGE `last_visited` `last_visit_time` datetime DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `user_info`
  ADD `expiration_time` datetime DEFAULT NULL AFTER `last_visit_time`,
  ADD `extra` json NOT NULL;

ALTER TABLE `user_msg`
  ADD KEY `sender` (`sender`),
  ADD KEY `receiver` (`receiver`),
  ADD KEY `read_time` (`receiver`,`read_time`) USING BTREE;

ALTER TABLE `user_system_msg`
  ADD KEY `receiver` (`receiver`,`read_time`);
