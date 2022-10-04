<?php

class Auth {
	public static function check() {
		global $myUser;
		return $myUser != null;
	}
	public static function id() {
		global $myUser;
		return $myUser['username'];
	}
	public static function user() {
		global $myUser;
		return $myUser;
	}
	public static function login($username, $remember = true) {
		if (!validateUsername($username)) {
			return;
		}
		$_SESSION['username'] = $username;
		if ($remember) {
			$remember_token = DB::selectFirst("select remember_token from user_info where username = '$username'")['remember_token'];
			if ($remember_token == '') {
				$remember_token = uojRandString(60);
				DB::update("update user_info set remember_token = '$remember_token', last_login = now() where username = '$username'");
			}

			$_SESSION['last_login'] = time();
			$expire = time() + 60 * 60 * 24 * 7;
			Cookie::safeSet('uoj_username', $username, $expire, '/', array('httponly' => true));
			Cookie::safeSet('uoj_remember_token', $remember_token, $expire, '/', array('httponly' => true));
		}
	}
	public static function logout() {
		unset($_SESSION['username']);
		unset($_SESSION['last_login']);
		unset($_SESSION['last_visited']);
		Cookie::safeUnset('uoj_username', '/');
		Cookie::safeUnset('uoj_remember_token', '/');
		DB::update("update user_info set remember_token = '' where username = '".Auth::id()."'");
	}

	private static function initMyUser() {
		global $myUser;
		$myUser = null;
		
		Cookie::safeCheck('uoj_username', '/');
		Cookie::safeCheck('uoj_remember_token', '/');
		
		if (isset($_SESSION['username'])) {
			if (!validateUsername($_SESSION['username'])) {
				return;
			}
			$myUser = queryUser($_SESSION['username']);
			return;
		}

		$remember_token = Cookie::safeGet('uoj_remember_token', '/');
		if ($remember_token != null) {
			$username = Cookie::safeGet('uoj_username', '/');
			if (!validateUsername($username)) {
				return;
			}
			$myUser = queryUser($username);
			if ($myUser['remember_token'] !== $remember_token) {
				$myUser = null;
			}
			return;
		}
	}
	public static function init() {
		global $myUser;
		
		Auth::initMyUser();

		if ($myUser) {
			if ($myUser['usergroup'] == 'B') {
				$myUser = null;
			}
		}

		if ($myUser) {
			if (!isset($_SESSION['last_login'])) {
				$_SESSION['last_login'] = strtotime($myUser['last_login']);
			}
			if ((time() - $_SESSION['last_login']) > 60 * 60 * 24 * 7) {  // 1 week
				Auth::logout();
				$myUser = null;
			}
			
			$_SESSION["last_visited"] = time();
			DB::update("update user_info set remote_addr = '".DB::escape($_SERVER['REMOTE_ADDR'])."', http_x_forwarded_for = '".DB::escape($_SERVER['HTTP_X_FORWARDED_FOR'])."', last_visited = now() where username = '".DB::escape($myUser['username'])."'");
		}
	}
}
