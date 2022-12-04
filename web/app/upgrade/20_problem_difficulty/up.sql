ALTER TABLE `problems` ADD `difficulty` int NOT NULL DEFAULT '-1' AFTER `submit_num`;
ALTER TABLE `problems` MODIFY `extra_config` json NOT NULL;
