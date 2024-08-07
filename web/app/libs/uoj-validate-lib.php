<?php

function validateUsername($username) {
	return is_string($username) && preg_match('/^[a-zA-Z0-9_]{1,20}$/', $username);
}

function validatePassword($password) {
	return is_string($password) && preg_match('/^[a-z0-9]{32}$/', $password);
}

function validateEmail($email) {
	return is_string($email) && strlen($email) <= 50 && preg_match('/^(.+)@(.+)$/', $email);
}

function validateQQ($QQ) {
	return is_string($QQ) && strlen($QQ) <= 15 && preg_match('/^[0-9]{5,15}$/', $QQ);
}

function validateMotto($motto) {
	return is_string($motto) && ($len = mb_strlen($motto, 'UTF-8')) !== false && $len <= 1024;
}

function validateUInt($x, $len = 8) { // [0, 1000000000)
	if (!is_string($x)) {
		return false;
	}
	if ($x === '0') {
		return true;
	}
	return preg_match('/^[1-9][0-9]{0,' . $len . '}$/', $x);
}

function validateInt($x) {
	if (!is_string($x)) {
		return false;
	}
	if ($x[0] == '-') {
		$x = substr($x, 1);
	}
	return validateUInt($x);
}

function validateUFloat($x) {
	if (!is_string($x)) {
		return false;
	}
	if (!preg_match('/^([1-9][0-9]*|0)(\.[0-9]+)?$/', $x)) {
		return false;
	}
	return filter_var($x, FILTER_VALIDATE_FLOAT) !== false;
}

function validateFloat($x) {
	if (!is_string($x)) {
		return false;
	}
	if (strlen($x) == 0) {
		return false;
	}
	if ($x[0] == '-') {
		$x = substr($x, 1);
	}
	return validateUFloat($x);
}

function validateUploadedFile($name) {
	return isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name']);
}

function validateIP($ip) {
	return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function validateURL($url) {
	return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function validateString($x) {
	return is_string($x) ? '' : '不合法的字符串';
}

function validateGitHubUsername($username) {
	return is_string($username) && preg_match('/^[a-zA-Z0-9_-]{1,20}$/', $username);
}

function validateUserAndStoreByUsername($username, &$vdata) {
	if (!isset($vdata['user'])) {
		$vdata['user'] = [];
	}
	$user = UOJUser::query($username);
	if (!$user) {
		return "不存在名为{$username}的用户";
	}
	$vdata['user'][$username] = $user;
	return '';
}

function validateCommentId($id) {
	if (!validateUInt($id)) {
		return 'ID 不合法';
	}

	$comment = UOJBlogComment::query($id);

	if (!$comment) {
		return '评论不存在';
	}

	return ['error' => '', 'store' => $comment];
}

function validateNothing($x) {
	return '';
}

function is_short_string($str) {
	return is_string($str) && strlen($str) <= 256;
}

function validateCodeforcesProblemId($str) {
	return preg_match('/^(|GYM)[1-9][0-9]{0,5}[A-Z][1-9]?$/', $str);
}

function validateAtCoderProblemId($str) {
	return preg_match('/^(\w+)([a-z][1-9]?)$/', $str);
}

function validateLuoguProblemId($str) {
	return preg_match('/^P[1-9][0-9]{3,5}$/', $str);
}
