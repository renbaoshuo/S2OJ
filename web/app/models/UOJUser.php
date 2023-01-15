<?php

/**
 * This is a static class
 * Unlike other classes like UOJProblem, UOJSubmission, UOJUserBlog, etc.,
 * this class does not use UOJDataTrait!
 * The main reason is that user_info is used for too many times.
 * So everything should be done in a lightweight way.
 */
class UOJUser {
	const MAX_UA_LEN = 300;
	const MAX_HISTORY_LEN = 20;

	public static $visibility_codes = [
		'all' => [
			'html' => '',
		],
		'self' => [
			'html' => '<small class="text-muted">（仅自己可见）</small>',
		],
	];

	public static function query($username) {
		if (!validateUsername($username)) {
			return null;
		}
		return DB::selectFirst([
			"select * from user_info",
			"where", ['username' => $username]
		]);
	}

	public static function checkBasicInfo($user, $cfg = []) {
		$cfg += [
			'check_email' => true,
		];

		if (!isset($user['username']) || !validateUsername($user['username'])) {
			throw new UOJInvalidArgumentException('无效用户名');
		}
		if (UOJUser::query($user['username'])) {
			throw new UOJInvalidArgumentException('用户名已存在');
		}
		if (!isset($user['password']) || !validatePassword($user['password'])) {
			throw new UOJInvalidArgumentException('无效密码');
		}
		if ($cfg['check_email'] && (!isset($user['email']) || !validateEmail($user['email']))) {
			throw new UOJInvalidArgumentException('无效电子邮箱');
		}
	}

	public static function register($user, $cfg = []) {
		UOJUser::checkBasicInfo($user, $cfg);

		$password = getPasswordToStore($user['password'], $user['username']);

		$info = [
			'username' => $user['username'],
			'email' => $user['email'],
			'school' => $user['school'] ?: '',
			'password' => $password,
			'svn_password' => uojRandString(20),
			'register_time' => DB::now(),
			'extra' => '{}'
		];
		// 0 means non-existence, false means DB error.
		if (DB::selectExists("select 1 from user_info") === 0) {
			$info['usergroup'] = 'S';
		}
		DB::insert([
			"insert into user_info",
			DB::bracketed_fields(array_keys($info)),
			"values", DB::tuple($info)
		]);

		return $user;
	}

	public static function registerTmpAccount($user, $cfg = []) {
		UOJUser::checkBasicInfo($user, $cfg);

		$password = getPasswordToStore($user['password'], $user['username']);
		$extra = [
			'permissions' => [
				'problems' => [
					'view' => false,
					'download_testdata' => false,
					'create' => false,
					'manage' => false,
				],
				'contests' => [
					'view' => false,
					'register' => false,
					'create' => false,
					'start_final_test' => false,
					'manage' => false,
				],
				'lists' => [
					'view' => false,
					'create' => false,
					'manage' => false,
				],
				'groups' => [
					'view' => false,
					'create' => false,
					'manage' => false,
				],
				'blogs' => [
					'view' => false,
					'create' => false,
					'manage' => false,
				],
				'users' => [
					'view' => false,
					'upload_image' => false,
				],
			],
		];

		$info = [
			'username' => $user['username'],
			'usergroup' => 'T',
			'email' => $user['email'],
			'school' => $user['school'] ?: '',
			'password' => $password,
			'svn_password' => uojRandString(20),
			'register_time' => DB::now(),
			'expiration_time' => $user['expiration_time'],
			'extra' => json_encode($extra),
		];

		DB::insert([
			"insert into user_info",
			DB::bracketed_fields(array_keys($info)),
			"values", DB::tuple($info)
		]);

		return $user;
	}

	public static function registerTmpACMTeamAccount($team, $cfg = []) {
		UOJUser::checkBasicInfo($team, $cfg);

		if (!isset($team['expiration_time'])) {
			throw new UOJInvalidArgumentException('无效账号过期时间');
		}

		$password = getPasswordToStore($team['password'], $team['username']);

		$team['extra'] = json_encode([
			'acm' => [
				'contest_name' => $team['contest_name'],
				'team_name' => $team['team_name'],
				'members' => $team['members']
			]
		], JSON_UNESCAPED_UNICODE);

		DB::insert([
			"insert into user_info",
			"(usergroup, username, email, password, svn_password, register_time, expiration_time, extra)",
			"values", DB::tuple([
				'T', $team['username'], $team['email'], $password, uojRandString(20),
				DB::now(), $team['expiration_time'], $team['extra']
			])
		]);

		return $team;
	}

	public static function registerTmpACMTeamAccountFromText($text, $contest_name, $expiration_time) {
		$fields = array_map('trim', str_getcsv($text));

		if (count($fields) < 7) {
			throw new UOJInvalidArgumentException('格式不合规范');
		}

		$num = (int)$fields[4];
		if (count($fields) != 5 + $num * 2) {
			throw new UOJInvalidArgumentException('格式不合规范');
		}

		$mem = [];
		for ($i = 0; $i < $num; $i++) {
			$mem[] = [
				'name' => $fields[5 + $i * 2],
				'organization' => $fields[5 + $i * 2 + 1]
			];
		}

		$team = [
			'username' => $fields[0],
			'password' => hash_hmac('md5', $fields[1], getPasswordClientSalt()),
			'email' => $fields[2],
			'contest_name' => $contest_name,
			'expiration_time' => $expiration_time,
			'team_name' => $fields[3],
			'members' => $mem
		];

		return UOJUser::registerTmpACMTeamAccount($team);
	}

	public static function getAccountStatus($user) {
		if ($user['usergroup'] == 'B') {
			return 'banned';
		} elseif ($user['usergroup'] == 'T' && UOJTime::$time_now > new DateTime($user['expiration_time'])) {
			return 'expired';
		} else {
			return 'ok';
		}
	}

	public static function getUserColor($user) {
		$extra = UOJUser::getExtra($user);

		return UOJUser::getUserColor2($user['usergroup'], $extra['username_color']);
	}

	public static function getUserColor2($usergroup, $custom_color = null) {
		if ($usergroup == 'B') {
			return '#996600';
		}

		if ($usergroup == 'T') {
			return '#707070';
		}

		if ($usergroup == 'S') {
			return $custom_color ?: '#9d3dcf';
		}

		// 前管理员设置颜色为紫色的，颜色改为蓝色
		if ($custom_color == '#9d3dcf') {
			return '#0d6efd';
		}

		return $custom_color ?: '#0d6efd';
	}

	public static function getLink($user) {
		if (is_string($user)) {
			$info = UOJUser::query($user);

			if (!$info) {
				return $user;
			} else {
				$user = $info;
			}
		}

		$realname = $user['realname'];

		if ($user['usertype'] == 'teacher') {
			$realname .= '老师';
		}

		if (!Auth::check()) {
			$realname = '';
		}

		return HTML::tag('span', [
			'class' => 'uoj-username',
			'data-color' => UOJUser::getUserColor($user),
			// 未登录不可查看真实姓名
			'data-realname' => trim(HTML::escape($realname)),
		], $user['username']);
	}

	public static function getUpdatedExtraVisitHistory($history, $cur) {
		$new_h = [];
		$oldest = clone UOJTime::$time_now;
		$oldest->modify('-1 month');
		for ($i = 0; $i < count($history); $i++) {
			if (UOJTime::str2time($history[$i]['last']) >= $oldest) {
				$new_h[] = $history[$i];
			}
		}

		if ($cur === null) {
			return $new_h;
		}

		for ($i = 0; $i < count($new_h); $i++) {
			if ($new_h[$i]['addr'] == $cur['addr'] && $new_h[$i]['ua'] == $cur['ua']) {
				$new_h[$i]['last'] = $cur['last'];
				return $new_h;
			}
		}
		if (count($new_h) < UOJUser::MAX_HISTORY_LEN) {
			$new_h[] = $cur;
			return $new_h;
		}
		$p = 0;
		for ($i = 1; $i < count($new_h); $i++) {
			if (strcmp($new_h[$i]['last'], $new_h[$p]['last']) < 0) {
				$p = $i;
			}
		}
		$new_h[$p] = $cur;
		return $new_h;
	}

	public static function sortExtraVisitHistory(&$history) {
		usort($history, function ($a, $b) {
			return -strcmp($a['last'], $b['last']);
		});
	}

	public static function getExtra($user) {
		$extra = json_decode($user['extra'], true);
		if ($extra === null) {
			$extra = [];
		}
		mergeConfig($extra, [
			'permissions' => UOJContext::getMeta('users_default_permissions'),
			'social' => [
				'codeforces' => null,
				'github' => null,
				'website' => null,
			],
			'image_hosting' => [
				'total_size_limit' => 104857600, // 100 MiB
			],
			'history' => [],
			'show_email' => 'all',
			'show_qq' => 'all',
			'avatar_source' => 'gravatar',
			'username_color' => isSuperUser($user) ? '#9d3dcf' : '#0d6efd',
		]);
		return $extra;
	}

	public static function checkVisibility(string $code, array $user, ?array $viewer) {
		switch ($code) {
			case 'all':
				return true;
			case 'self':
				return $viewer && $user['username'] === $viewer['username'];
			default:
				return false;
		}
	}

	public static function getVisibilityHTML(string $code) {
		return static::$visibility_codes[$code]['html'];
	}

	public static function viewerCanSeeComponents(array $user, ?array $viewer) {
		// assert($viewer can view $user's probile). This is always true because everyone's profile is public.
		$extra = UOJUser::getExtra($user);
		return [
			'email' => UOJUser::checkVisibility($extra['show_email'], $user, $viewer),
			'qq' => UOJUser::checkVisibility($extra['show_qq'], $user, $viewer)
		];
	}

	public static function checkPermission(array $user = null, string $perm = '') {
		if ($user == null) {
			return false;
		}

		$extra = UOJUser::getExtra($user);
		$cur = $extra['permissions'];

		foreach (explode('.', $perm) as $p) {
			if (!is_assoc($cur) || !isset($cur[$p])) {
				return false;
			}
			$cur = $cur[$p];
		}

		return $cur;
	}

	public static function updateVisitHistory($user, $info) {
		$extra = UOJUser::getExtra($user);
		$cur = [
			'addr' => $info['remote_addr'],
			'ua' => substr($info['http_user_agent'], 0, UOJUser::MAX_UA_LEN),
			'last' => UOJTime::$time_now_str
		];

		$extra['history'] = UOJUser::getUpdatedExtraVisitHistory($extra['history'], $cur);

		$user['remote_addr'] = $info['remote_addr'];
		$user['http_x_forwarded_for'] = $info['http_x_forwarded_for'];
		$user['extra'] = json_encode($extra, JSON_UNESCAPED_UNICODE);

		DB::update([
			'update user_info',
			'set', [
				'remote_addr' => $user['remote_addr'],
				'http_x_forwarded_for' => $user['http_x_forwarded_for'],
				'last_visit_time' => DB::now(),
				'extra' => $user['extra']
			], "where", ["username" => $user['username']]
		]);

		return $user;
	}
}
