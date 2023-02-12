<?php
error_reporting(E_ALL ^ E_NOTICE);

spl_autoload_register(function ($class_name) {
	$lib = $_SERVER['DOCUMENT_ROOT'] . '/app/models/' . $class_name . '.php';
	if (file_exists($lib)) {
		require_once $lib;
	}
});

function requireLib($name) { // html lib
	global $REQUIRE_LIB;
	$REQUIRE_LIB[$name] = '';
}
function requirePHPLib($name) { // uoj php lib
	require_once $_SERVER['DOCUMENT_ROOT'] . '/app/libs/uoj-' . $name . '-lib.php';
}

requirePHPLib('expection');
requirePHPLib('validate');
requirePHPLib('query');
requirePHPLib('rand');
requirePHPLib('utility');
requirePHPLib('security');
requirePHPLib('contest');
requirePHPLib('html');

Session::init();
UOJTime::init();
DB::init();

$myUser  = null;
Auth::init();

UOJLocale::init();
