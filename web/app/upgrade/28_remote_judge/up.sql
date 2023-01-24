ALTER TABLE `problems` ADD `type` varchar(20) NOT NULL DEFAULT 'local' AFTER `difficulty`;
ALTER TABLE `problems` ADD KEY `type` (`type`);

ALTER TABLE `problems_contents` ADD `remote_content` longtext COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' AFTER `id`;

insert into judger_info (judger_name, password, ip, display_name, description) values ('remote_judger', '_judger_password_', 'uoj-remote-judger', '远端评测机', '用于桥接远端 OJ 评测机的虚拟评测机。');
