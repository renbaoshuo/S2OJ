<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/app/libs/uoj-lib.php';

// Create a new scheduler
$scheduler = new GO\Scheduler([
	'tempDir' => '/tmp'
]);

echo '[UOJScheduler] Init', "\n";

// =========== JOBS ===========

// Email
$scheduler->call('UOJMail::cronSendEmail', [], 'cronSendEmail')
	->at('* * * * *')
	->onlyOne()
	->before(function () {
		echo "[cronSendEmail] started at " . time() . "\n";
	})
	->then(function () {
		echo "[cronSendEmail] ended at " . time() . "\n";
	});

// ============================

// Let the scheduler execute jobs which are due.
$scheduler->run();
