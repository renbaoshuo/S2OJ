<?php
return [
	'profile' => [
		'oj-name' => '石家庄二中信息学在线评测系统',
		'oj-name-short' => 'S2OJ',
		'administrator' => 'root',
		'admin-email' => 'admin@sjzezoj.com',
		'QQ-group' => '',
		'ICP-license' => '冀ICP备2020028886号',
	],
	'database' => [
		'database' => 'app_uoj233',
		'username' => 'root',
		'password' => 'root',
		'host' => 'uoj-db',
		'port' => '3306',
	],
	'security' => [
		'user' => [
			'client_salt' => 'salt_0',
		],
		'cookie' => [
			'checksum_salt' => ['salt_1', 'salt_2', 'salt_3'],
		],
	],
	'mail' => [
		'noreply' => [
			'username' => 'noreply@local_uoj.ac',
			'password' => '_mail_noreply_password_',
			'host' => 'smtp.local_uoj.ac',
			'secure' => 'tls',
			'port' => 587,
		]
	],
	'judger' => [
		'socket' => [
			'port' => '2333',
			'password' => '_judger_socket_password_'
		],
	],
	'switch' => [
		'blog-domain-mode' => 3,
		'open-register' => false,
	],
];
