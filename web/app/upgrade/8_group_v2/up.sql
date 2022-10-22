RENAME TABLE assignments TO groups_assignments;
ALTER TABLE `groups_assignments` DROP COLUMN `id`;
ALTER TABLE `groups_assignments` ADD PRIMARY KEY (`group_id`, `list_id`);
ALTER TABLE `groups_assignments` DROP COLUMN `create_time`;
ALTER TABLE `groups_assignments` CHANGE COLUMN `deadline` `end_time` datetime NOT NULL;
ALTER TABLE `countdowns` CHANGE COLUMN `endtime` `end_time` datetime NOT NULL;
ALTER TABLE `submissions` ADD KEY `score` (`problem_id`, `submitter`, `score`);
