<?php

function getPasswordToStore($password, $username) {
	return md5($username . $password);
}
function checkPassword($user, $password) {
	return $user['password'] == md5($user['username'] . $password);
}
function getPasswordClientSalt() {
	return UOJConfig::$data['security']['user']['client_salt'];
}

function crsf_token() {
	if (!isset($_SESSION['_token'])) {
		$_SESSION['_token'] = uojRandString(60);
	}
	return $_SESSION['_token'];
}
function crsf_check() {
	if (isset($_POST['_token'])) {
		$_token = $_POST['_token'];
	} elseif (isset($_GET['_token'])) {
		$_token = $_GET['_token'];
	} else {
		return false;
	}
	return $_token === $_SESSION['_token'];
}
function crsf_defend() {
	if (!crsf_check()) {
		UOJResponse::page403('页面已过期（可能页面真的过期了，也可能是刚才你访问的网页没有完全加载，也可能是你的浏览器版本太老）');
	}
}

function submission_frequency_check() {
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT1S"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 1) {
		return false;
	}

	// use the implementation below if OJ is under attack
	/*
	// 1
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT3S"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 1) {
		return false;
	}
	
	// 2
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT1M"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 6) {
		return false;
	}
	
	// 3
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT30M"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 30) {
		return false;
	}
	*/
	
	return true;
}
