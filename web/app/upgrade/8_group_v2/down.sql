RENAME TABLE groups_assignments TO assignments;
ALTER TABLE `assignments` ADD COLUMN `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `assignments` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);
ALTER TABLE `assignments` ADD COLUMN `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `assignments` CHANGE COLUMN `end_time` `deadline` datetime NOT NULL;
ALTER TABLE `countdowns` CHANGE COLUMN `end_time` `endtime` datetime NOT NULL;
