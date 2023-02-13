<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/app/libs/uoj-lib.php';

// TODO: more beautiful argv parser

$handlers = [
	'upgrade:up' => function ($name) {
		if (func_num_args() != 1) {
			print("php cli.php upgrade:up <name>\n");
			exit(1);
		}
		Upgrader::transaction(function () use ($name) {
			Upgrader::up($name);
		});
		print("finished!\n");
	},
	'upgrade:down' => function ($name) {
		if (func_num_args() != 1) {
			print("php cli.php upgrade:down <name>\n");
			exit(1);
		}
		Upgrader::transaction(function () use ($name) {
			Upgrader::down($name);
		});
		print("finished!\n");
	},
	'upgrade:refresh' => function ($name) {
		if (func_num_args() != 1) {
			print("php cli.php upgrade:refresh <name>\n");
			exit(1);
		}
		Upgrader::transaction(function () use ($name) {
			Upgrader::refresh($name);
		});
		print("finished!\n");
	},
	'upgrade:remove' => function ($name) {
		if (func_num_args() != 1) {
			print("php cli.php upgrade:remove <name>\n");
			exit(1);
		}
		Upgrader::transaction(function () use ($name) {
			Upgrader::remove($name);
		});
		print("finished!\n");
	},
	'upgrade:latest' => function () {
		if (func_num_args() != 0) {
			print("php cli.php upgrade:latest\n");
			exit(1);
		}
		Upgrader::transaction(function () {
			Upgrader::upgradeToLatest();
		});
		print("finished!\n");
	},
	'upgrade:remove-all' => function () {
		if (func_num_args() != 0) {
			print("php cli.php upgrade:remove-all\n");
			exit(1);
		}
		Upgrader::transaction(function () {
			Upgrader::removeAll();
		});
		print("finished!\n");
	},
	'email:send-all' => function () {
		if (func_num_args() != 0) {
			print("php cli.php email:send-all\n");
			exit(1);
		}
		UOJMail::cronSendEmail();
		print("finished!\n");
	},
	'help' => 'showHelp',
];

function showHelp() {
	global $handlers;
	echo "UOJ Command-Line Interface\n";
	echo "php cli.php <task-name> params1 params2 ...\n";
	echo "\n";
	echo "The following tasks are available:\n";
	foreach ($handlers as $cmd => $handler) {
		echo "\t$cmd\n";
	}
}

if (count($argv) <= 1) {
	showHelp();
	die();
}

if (!isset($handlers[$argv[1]])) {
	echo "Invalid parameters.\n";
	showHelp();
	die();
}

call_user_func_array($handlers[$argv[1]], array_slice($argv, 2));
