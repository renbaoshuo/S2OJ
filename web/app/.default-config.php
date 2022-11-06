<?php
return [
	'profile' => [
		'oj-name' => '石家庄二中信息学在线评测系统',
		'oj-name-short' => 'S2OJ',
		'administrator' => 'root',
		'admin-email' => 'admin@sjzezoj.com',
		'QQ-group' => '',
		'ICP-license' => '冀ICP备2020028886号',
		's2oj-version' => 'dev'
	],
	'database' => [
		'database' => 'app_uoj233',
		'username' => 'root',
		'password' => '_database_password_',
		'host' => '127.0.0.1',
		'port' => '3306',
	],
	'web' => [
		'domain' => null,
		'main' => [
			'protocol' => 'http',
			'host' => '_httpHost_',
			'port' => '80/443'
		],
		'blog' => [
			'protocol' => 'http',
			'host' => '_httpHost_',
			'port' => '80/443'
		]
	],
	'security' => [
		'user' => [
			'client_salt' => 'salt0'
		],
		'cookie' => [
			'checksum_salt' => ['salt1', 'salt2', 'salt3']
		],
	],
	'mail' => [
		'noreply' => [
			'username' => 'noreply@local_uoj.ac',
			'password' => '_mail_noreply_password_',
			'host' => 'smtp.local_uoj.ac',
			'secure' => 'tls',
			'port' => 587
		]
	],
	'judger' => [
		'socket' => [
			'port' => '233',
			'password' => '_judger_socket_password_'
		]
	],
	'switch' => [
		'blog-domain-mode' => 3,
		'open-register' => false
	]
];
