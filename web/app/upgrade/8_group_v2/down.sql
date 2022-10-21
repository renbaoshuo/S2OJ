RENAME TABLE groups_assignments TO assignments;
ALTER TABLE `assignments` ADD COLUMN `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `assignments` CHANGE COLUMN `end_time` `deadline` datetime NOT NULL;
ALTER TABLE `countdowns` CHANGE COLUMN `end_time` `endtime` datetime NOT NULL;
