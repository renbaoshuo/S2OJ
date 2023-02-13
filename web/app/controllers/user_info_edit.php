<?php
requireLib('md5');
requirePHPLib('form');

if (!Auth::check()) {
	redirectToLogin();
}

($user = UOJUser::query($_GET['username'])) || UOJResponse::page404();
(isSuperUser(Auth::user()) || Auth::id() == $user['username']) || UOJResponse::page403();
$extra = UOJUser::getExtra($user);

if (isset($_GET['tab'])) {
	$cur_tab = $_GET['tab'];
} else {
	$cur_tab = 'profile';
}

$tabs_info = [
	'profile' => [
		'name' => '<i class="bi bi-person-fill"></i> 个人资料',
		'url' => "/user/{$user['username']}/edit/profile",
	],
	'password' => [
		'name' => '<i class="bi bi-lock-fill"></i> 修改密码',
		'url' => "/user/{$user['username']}/edit/password",
	],
	'privilege' => [
		'name' => '<i class="bi bi-key-fill"></i> 特权',
		'url' => "/user/{$user['username']}/edit/privilege",
	]
];

if (!isset($tabs_info[$cur_tab])) {
	become404Page();
}

if ($cur_tab == 'profile') {
	$update_profile_form = new UOJForm('update_profile');
	$username = UOJLocale::get('username');
	$avatar = UOJLocale::get('avatar');
	$update_profile_form->appendHTML(<<<EOD
	<div class="mb-3">
		<label for="input-username" class="form-label">$username</label>
		<input type="text" class="form-control" id="input-username" aria-describedby="help-username" value="{$user['username']}" disabled>
		<div id="help-username" class="form-text">用户名不能被修改。</div>
	</div>
EOD);
	if (isSuperUser(Auth::user())) {
		$update_profile_form->addInput(
			'realname',
			[
				'div_class' => 'mb-3',
				'label' => UOJLocale::get('user::real name'),
				'default_value' => $user['realname'],
				'validator_php' => function ($realname, &$vdata) {
					$vdata['realname'] = $realname;

					return '';
				},
			]
		);
	} else {
		$real_name = UOJLocale::get('user::real name');
		$update_profile_form->appendHTML(<<<EOD
	<div class="mb-3">
		<label for="input-realname" class="form-label">$real_name</label>
		<input type="text" class="form-control" id="input-realname" aria-describedby="help-realname" value="{$user['realname']}" disabled>
		<div id="help-realname" class="form-text">只有管理员才能修改用户的真实姓名。</div>
	</div>
EOD);
	}
	if (isTmpUser($user)) {
		if (isSuperUser(Auth::user())) {
			$update_profile_form->addInput(
				'expiration_time',
				[
					'div_class' => 'mb-3',
					'label' => UOJLocale::get('user::expiration time'),
					'default_value' => $user['expiration_time'],
					'validator_php' => function ($str, &$vdata) {
						try {
							$vdata['expiration_time'] = new DateTime($str);
						} catch (Exception $e) {
							return '无效时间格式';
						}

						return '';
					},
				]
			);
		} else {
			$expiration_time = UOJLocale::get('user::expiration time');
			$update_profile_form->appendHTML(<<<EOD
		<div class="mb-3">
			<label for="input-expiration_time" class="form-label">$expiration_time</label>
			<input type="text" class="form-control" id="input-expiration_time" aria-describedby="help-expiration_time" value="{$user['expiration_time']}" disabled>
			<div id="help-expiration_time" class="form-text">只有管理员才能修改用户的账号过期时间。</div>
		</div>
	EOD);
		}
	} else {
		$expiration_time = UOJLocale::get('user::expiration time');
		$expiration_help_text = isSuperUser(Auth::user())
			? '只有用户组别为「临时用户」的用户才能被修改过期时间。'
			: '只有管理员才能修改用户的账号过期时间。';
		$update_profile_form->appendHTML(<<<EOD
	<div class="mb-3">
		<label for="input-expiration_time" class="form-label">$expiration_time</label>
		<input type="text" class="form-control" id="input-expiration_time" aria-describedby="help-expiration_time" value="永不过期" disabled>
		<div id="help-expiration_time" class="form-text">$expiration_help_text</div>
	</div>
EOD);
	}
	$update_profile_form->addCheckboxes('avatar_source', [
		'div_class' => 'mb-3',
		'label' => UOJLocale::get('user::avatar source'),
		'label_class' => 'me-3',
		'default_value' => $extra['avatar_source'] ?: 'gravatar',
		'select_class' => 'd-inline-block',
		'option_div_class' => 'form-check d-inline-block ms-2',
		'options' => [
			'gravatar' => 'Gravatar',
			'qq' => 'QQ',
		],
		'help' => UOJLocale::get('change avatar help'),
	]);
	$update_profile_form->addInput(
		'email',
		[
			'div_class' => 'mb-3',
			'type' => 'email',
			'label' => UOJLocale::get('email'),
			'default_value' => $user['email'] ?: '',
			'validator_php' => function ($email, &$vdata) {
				if ($email && !validateEmail($email)) {
					return 'Email 格式不合法。';
				}

				$vdata['email'] = $email;

				return '';
			},
		]
	);
	$update_profile_form->addInput(
		'qq',
		[
			'div_class' => 'mb-3',
			'label' => UOJLocale::get('QQ'),
			'default_value' => $user['qq'] == 0 ? '' : $user['qq'],
			'validator_php' => function ($qq, &$vdata) {
				if ($qq && !validateQQ($qq)) {
					return 'QQ 格式不合法。';
				}

				$vdata['qq'] = $qq;

				return '';
			},
		]
	);
	$update_profile_form->addInput(
		'github',
		[
			'div_class' => 'mb-3',
			'label' => 'GitHub',
			'default_value' => $extra['social']['github'] ?: '',
			'validator_php' => function ($github, &$vdata) {
				if ($github && !validateGitHubUsername($github)) {
					return 'GitHub 用户名不合法。';
				}

				$vdata['github'] = $github;

				return '';
			},
		]
	);
	if (isSuperUser(Auth::user())) {
		$update_profile_form->addInput(
			'school',
			[
				'div_class' => 'mb-3',
				'label' => UOJLocale::get('school'),
				'default_value' => $user['school'] ?: '',
				'validator_php' => function ($school, &$vdata) {
					$vdata['school'] = $school;

					return '';
				},
			]
		);
	} else {
		$school = UOJLocale::get('school');
		$update_profile_form->appendHTML(<<<EOD
	<div class="mb-3">
		<label for="input-school" class="form-label">$school</label>
		<input type="text" class="form-control" id="input-school" aria-describedby="help-school" value="{$user['school']}" disabled>
		<div id="help-school" class="form-text">只有管理员才能修改用户所属学校。</div>
	</div>
EOD);
	}
	$update_profile_form->addCheckboxes('sex', [
		'div_class' => 'mb-3',
		'label' => UOJLocale::get('sex'),
		'label_class' => 'me-3',
		'default_value' => $user['sex'],
		'select_class' => 'd-inline-block',
		'option_div_class' => 'form-check d-inline-block ms-2',
		'options' => [
			'U' => UOJLocale::get('refuse to answer'),
			'M' => UOJLocale::get('male'),
			'F' => UOJLocale::get('female'),
		],
	]);
	$update_profile_form->addInput(
		'motto',
		[
			'div_class' => 'mb-3',
			'label' => UOJLocale::get('motto'),
			'default_value' => $user['motto'] ?: '',
			'validator_php' => function ($motto, &$vdata) {
				if (!validateMotto($motto)) {
					return '格言格式不合法';
				}

				$vdata['motto'] = $motto;

				return '';
			},
		]
	);
	$update_profile_form->addInput(
		'codeforces',
		[
			'div_class' => 'mb-3',
			'label' => UOJLocale::get('codeforces handle'),
			'default_value' => $extra['social']['codeforces'] ?: '',
			'validator_php' => function ($codeforces, &$vdata) {
				if ($codeforces && !validateUsername($codeforces)) {
					return 'Codeforces 用户名格式不合法。';
				}

				$vdata['codeforces'] = $codeforces;

				return '';
			},
		]
	);
	$update_profile_form->addInput(
		'website',
		[
			'div_class' => 'mb-3',
			'label' => UOJLocale::get('user::website'),
			'default_value' => $extra['social']['website'] ?: '',
			'validator_php' => function ($url, &$vdata) {
				if ($url && !validateURL($url)) {
					return '链接格式不合法。';
				}

				$vdata['website'] = $url;

				return '';
			},
		]
	);
	if ($user['usergroup'] == 'B') {
		$update_profile_form->appendHTML(<<<EOD
			<div class="mb-3">
				<label for="input-username_color" class="form-label">用户名颜色</label>
				<input type="text" class="form-control" id="input-username_color" aria-describedby="help-username_color" value="棕色 - #996600" disabled>
				<div id="help-username_color" class="form-text">被封禁的用户无法修改用户名颜色。</div>
			</div>
		EOD);
	} else if ($user['usergroup'] == 'T') {
		$update_profile_form->appendHTML(<<<EOD
			<div class="mb-3">
				<label for="input-username_color" class="form-label">用户名颜色</label>
				<input type="text" class="form-control" id="input-username_color" aria-describedby="help-username_color" value="灰色 - #707070" disabled>
				<div id="help-username_color" class="form-text">临时用户无法修改用户名颜色。</div>
			</div>
		EOD);
	} else {
		$additional_colors = [];

		if (isSuperUser($user)) {
			$additional_colors['#9d3dcf'] = '紫色 - #9d3dcf';
		}

		$update_profile_form->addSelect('username_color', [
			'div_class' => 'mb-3',
			'label' => '用户名颜色',
			'default_value' => $extra['username_color'],
			'options' => $additional_colors + [
				'#0d6efd' => '蓝色 - #0d6efd',
				'#2da44e' => '绿色 - #2da44e',
				'#e85aad' => '粉色 - #e85aad',
				'#f32a38' => '红色 - #f32a38',
				'#f57c00' => '橙色 - #f57c00',
				'#00acc1' => '青色 - #00acc1',
			],
		]);
	}
	$update_profile_form->handle = function (&$vdata) use ($user) {
		$data = [
			'email' => $vdata['email'],
			'qq' => $vdata['qq'],
			'sex' => $_POST['sex'],
			'motto' => $vdata['motto'],
		];

		if (isSuperUser(Auth::user())) {
			$data['realname'] = $vdata['realname'];
			$data['school'] = $vdata['school'];

			if (isTmpUser($user)) {
				$data['expiration_time'] = $vdata['expiration_time']->format(UOJTime::FORMAT);
			}
		}

		DB::update([
			"update user_info",
			"set", $data,
			"where", ["username" => $user['username']]
		]);

		DB::update([
			"update user_info",
			"set", [
				'extra' => DB::json_set(
					'extra',
					'$.avatar_source',
					$_POST['avatar_source'],
					'$.social.github',
					$vdata['github'],
					'$.social.codeforces',
					$vdata['codeforces'],
					'$.social.website',
					$vdata['website'],
					'$.username_color',
					$_POST['username_color']
				),
			],
			"where", ["username" => $user['username']]
		]);

		dieWithJsonData(['status' => 'success']);
	};
	$update_profile_form->config['submit_container']['class'] = 'text-center mt-3';
	$update_profile_form->config['submit_button']['class'] = 'btn btn-secondary';
	$update_profile_form->config['submit_button']['text'] = '更新';
	$update_profile_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#result-alert')
			.html('个人信息修改成功！')
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#result-alert')
			.html('个人信息修改失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
	$update_profile_form->runAtServer();
} elseif ($cur_tab == 'password') {
	if (isset($_POST['submit-change_password']) && $_POST['submit-change_password'] == 'change_password') {
		$old_password = $_POST['current_password'];
		$new_password = $_POST['new_password'];

		if (!validatePassword($old_password) || !checkPassword($user, $old_password)) {
			dieWithJsonData(['status' => 'error', 'message' => '旧密码错误']);
		}

		if (!validatePassword($new_password)) {
			dieWithJsonData(['status' => 'error', 'message' => '新密码不合法']);
		}

		if ($old_password == $new_password) {
			dieWithJsonData(['status' => 'error', 'message' => '新密码不能与旧密码相同']);
		}

		DB::update([
			"update user_info",
			"set", [
				"password" => getPasswordToStore($new_password, $user['username']),
				"remember_token" => "",
			],
			"where", ["username" => $user['username']]
		]);

		dieWithAlert('密码修改成功！');
	}
} elseif ($cur_tab == 'privilege') {
	$users_default_permissions = UOJContext::getMeta('users_default_permissions');
	$type_text = UOJLocale::get('user::normal user');
	if ($user['usergroup'] == 'S') {
		$type_text = UOJLocale::get('user::super user');
	} elseif ($user['usergroup'] == 'T') {
		$type_text = UOJLocale::get('user::tmp user');
	} elseif ($user['usergroup'] == 'B') {
		$type_text = UOJLocale::get('user::banned user');
	}
	$disabled = !isSuperUser(Auth::user());
	$update_user_permissions_form = new UOJForm('update_user_permissions');
	if ($disabled) {
		$update_user_permissions_form->config['no_submit'] = true;
	}
	$update_user_permissions_form->appendHTML(HTML::tag('span', [], UOJLocale::get('user::user group')));
	$update_user_permissions_form->appendHTML(HTML::tag('span', ['class' => 'd-inline-block ms-3'], $type_text));
	$update_user_permissions_form->addSelect('user_type', [
		'label' => '账号类型',
		'options' => [
			'student' => '学生',
			'teacher' => '老师',
			'system' => '系统',
		],
		'div_class' => 'my-3 row gy-2 gx-3 align-items-center',
		'label_class' => 'form-label col-auto',
		'select_class' => 'form-select w-auto col-auto',
	]);
	$update_user_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '题目'));
	$update_user_permissions_form->addCheckbox('problems__view', [
		'checked' => $extra['permissions']['problems']['view'],
		'label' => '查看题目',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('problems__download_testdata', [
		'checked' => $extra['permissions']['problems']['download_testdata'],
		'label' => '下载测试数据',
		'role' => 'switch',
		'help' => '请谨慎开启此权限，以防数据泄露。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('problems__create', [
		'checked' => $extra['permissions']['problems']['create'],
		'label' => '新建题目',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('problems__manage', [
		'checked' => $extra['permissions']['problems']['manage'],
		'label' => '管理题目',
		'role' => 'switch',
		'help' => '若用户不具有「新建题目」权限，则只能对现有题目进行管理。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '比赛'));
	$update_user_permissions_form->addCheckbox('contests__view', [
		'checked' => $extra['permissions']['contests']['view'],
		'label' => '查看比赛',
		'role' => 'switch',
		'help' => '若用户不具有此权限，则只显示已报名过的比赛列表及详情。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('contests__register', [
		'checked' => $extra['permissions']['contests']['register'],
		'label' => '报名比赛',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('contests__create', [
		'checked' => $extra['permissions']['contests']['create'],
		'label' => '新建比赛',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('contests__start_final_test', [
		'checked' => $extra['permissions']['contests']['start_final_test'],
		'label' => '开始比赛最终测试',
		'role' => 'switch',
		'help' => '拥有此权限的用户可以代为开始比赛最终测试。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('contests__manage', [
		'checked' => $extra['permissions']['contests']['manage'],
		'label' => '管理比赛',
		'role' => 'switch',
		'help' => '若用户不具有「新建比赛」权限，则只能对现有比赛进行管理。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '题单'));
	$update_user_permissions_form->addCheckbox('lists__view', [
		'checked' => $extra['permissions']['lists']['view'],
		'label' => '查看题单',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('lists__create', [
		'checked' => $extra['permissions']['lists']['create'],
		'label' => '新建题单',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('lists__manage', [
		'checked' => $extra['permissions']['lists']['manage'],
		'label' => '管理题单',
		'role' => 'switch',
		'help' => '若用户不具有「新建题单」权限，则只能对现有题单进行管理。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '小组'));
	$update_user_permissions_form->addCheckbox('groups__view', [
		'checked' => $extra['permissions']['groups']['view'],
		'label' => '查看小组',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('groups__create', [
		'checked' => $extra['permissions']['groups']['create'],
		'label' => '新建小组',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('groups__manage', [
		'checked' => $extra['permissions']['groups']['manage'],
		'label' => '管理小组',
		'role' => 'switch',
		'help' => '若用户不具有「新建小组」权限，则只能对现有小组进行管理。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '博客'));
	$update_user_permissions_form->addCheckbox('blogs__view', [
		'checked' => $extra['permissions']['blogs']['view'],
		'label' => '查看博客',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('blogs__create', [
		'checked' => $extra['permissions']['blogs']['create'],
		'label' => '新建博客',
		'role' => 'switch',
		'help' => '',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('blogs__manage', [
		'checked' => $extra['permissions']['blogs']['manage'],
		'label' => '管理博客',
		'role' => 'switch',
		'help' => '若用户不具有「新建博客」权限，则只能对现有博客进行管理。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '用户'));
	$update_user_permissions_form->addCheckbox('users__view', [
		'checked' => $extra['permissions']['users']['view'],
		'label' => '查看用户',
		'role' => 'switch',
		'help' => '若用户不具有此权限，则不能查看他人的个人资料。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->addCheckbox('users__upload_image', [
		'checked' => $extra['permissions']['users']['upload_image'],
		'label' => '上传图片',
		'role' => 'switch',
		'help' => '若用户不具有此权限，则不能使用图床功能。',
		'disabled' => $disabled,
	]);
	$update_user_permissions_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert')
					.html('修改成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert')
					.html('修改失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
EOD);
	$update_user_permissions_form->handle = function () use ($user, $extra, $users_default_permissions) {
		$new_permissions = [
			'_placeholder' => '',
			'problems' => [
				'_placeholder' => '',
				// 'view' => isset($_POST['problems__view']),
				// 'download_testdata' => isset($_POST['problems__download_testdata']),
				// 'create' => isset($_POST['problems__create']),
				// 'manage' => isset($_POST['problems__manage']),
			],
			'contests' => [
				'_placeholder' => '',
				// 'view' => isset($_POST['contests__view']),
				// 'register' => isset($_POST['contests__register']),
				// 'create' => isset($_POST['contests__create']),
				// 'start_final_test' => isset($_POST['contests__start_final_test']),
				// 'manage' => isset($_POST['contests__manage']),
			],
			'lists' => [
				'_placeholder' => '',
				// 'view' => isset($_POST['lists__view']),
				// 'create' => isset($_POST['lists__create']),
				// 'manage' => isset($_POST['lists__manage']),
			],
			'groups' => [
				'_placeholder' => '',
				// 'view' => isset($_POST['groups__view']),
				// 'create' => isset($_POST['groups__create']),
				// 'manage' => isset($_POST['groups__manage']),
			],
			'blogs' => [
				'_placeholder' => '',
				// 'view' => isset($_POST['blogs__view']),
				// 'create' => isset($_POST['blogs__create']),
				// 'manage' => isset($_POST['blogs__manage']),
			],
			'users' => [
				'_placeholder' => '',
			]
		];

		if (isset($_POST['problems__view']) && !$users_default_permissions['problems']['view']) {
			$new_permissions['problems']['view'] = true;
		} elseif (!isset($_POST['problems__view']) && $users_default_permissions['problems']['view']) {
			$new_permissions['problems']['view'] = false;
		}

		if (isset($_POST['problems__download_testdata']) && !$users_default_permissions['problems']['download_testdata']) {
			$new_permissions['problems']['download_testdata'] = true;
		} elseif (!isset($_POST['problems__download_testdata']) && $users_default_permissions['problems']['download_testdata']) {
			$new_permissions['problems']['download_testdata'] = false;
		}

		if (isset($_POST['problems__create']) && !$users_default_permissions['problems']['create']) {
			$new_permissions['problems']['create'] = true;
		} elseif (!isset($_POST['problems__create']) && $users_default_permissions['problems']['create']) {
			$new_permissions['problems']['create'] = false;
		}

		if (isset($_POST['problems__manage']) && !$users_default_permissions['problems']['manage']) {
			$new_permissions['problems']['manage'] = true;
		} elseif (!isset($_POST['problems__manage']) && $users_default_permissions['problems']['manage']) {
			$new_permissions['problems']['manage'] = false;
		}

		if (isset($_POST['contests__view']) && !$users_default_permissions['contests']['view']) {
			$new_permissions['contests']['view'] = true;
		} elseif (!isset($_POST['contests__view']) && $users_default_permissions['contests']['view']) {
			$new_permissions['contests']['view'] = false;
		}

		if (isset($_POST['contests__register']) && !$users_default_permissions['contests']['register']) {
			$new_permissions['contests']['register'] = true;
		} elseif (!isset($_POST['contests__register']) && $users_default_permissions['contests']['register']) {
			$new_permissions['contests']['register'] = false;
		}

		if (isset($_POST['contests__create']) && !$users_default_permissions['contests']['create']) {
			$new_permissions['contests']['create'] = true;
		} elseif (!isset($_POST['contests__create']) && $users_default_permissions['contests']['create']) {
			$new_permissions['contests']['create'] = false;
		}

		if (isset($_POST['contests__start_final_test']) && !$users_default_permissions['contests']['start_final_test']) {
			$new_permissions['contests']['start_final_test'] = true;
		} elseif (!isset($_POST['contests__start_final_test']) && $users_default_permissions['contests']['start_final_test']) {
			$new_permissions['contests']['start_final_test'] = false;
		}

		if (isset($_POST['contests__manage']) && !$users_default_permissions['contests']['manage']) {
			$new_permissions['contests']['manage'] = true;
		} elseif (!isset($_POST['contests__manage']) && $users_default_permissions['contests']['manage']) {
			$new_permissions['contests']['manage'] = false;
		}

		if (isset($_POST['lists__view']) && !$users_default_permissions['lists']['view']) {
			$new_permissions['lists']['view'] = true;
		} elseif (!isset($_POST['lists__view']) && $users_default_permissions['lists']['view']) {
			$new_permissions['lists']['view'] = false;
		}

		if (isset($_POST['lists__create']) && !$users_default_permissions['lists']['create']) {
			$new_permissions['lists']['create'] = true;
		} elseif (!isset($_POST['lists__create']) && $users_default_permissions['lists']['create']) {
			$new_permissions['lists']['create'] = false;
		}

		if (isset($_POST['lists__manage']) && !$users_default_permissions['lists']['manage']) {
			$new_permissions['lists']['manage'] = true;
		} elseif (!isset($_POST['lists__manage']) && $users_default_permissions['lists']['manage']) {
			$new_permissions['lists']['manage'] = false;
		}

		if (isset($_POST['groups__view']) && !$users_default_permissions['groups']['view']) {
			$new_permissions['groups']['view'] = true;
		} elseif (!isset($_POST['groups__view']) && $users_default_permissions['groups']['view']) {
			$new_permissions['groups']['view'] = false;
		}

		if (isset($_POST['groups__create']) && !$users_default_permissions['groups']['create']) {
			$new_permissions['groups']['create'] = true;
		} elseif (!isset($_POST['groups__create']) && $users_default_permissions['groups']['create']) {
			$new_permissions['groups']['create'] = false;
		}

		if (isset($_POST['groups__manage']) && !$users_default_permissions['groups']['manage']) {
			$new_permissions['groups']['manage'] = true;
		} elseif (!isset($_POST['groups__manage']) && $users_default_permissions['groups']['manage']) {
			$new_permissions['groups']['manage'] = false;
		}

		if (isset($_POST['blogs__view']) && !$users_default_permissions['blogs']['view']) {
			$new_permissions['blogs']['view'] = true;
		} elseif (!isset($_POST['blogs__view']) && $users_default_permissions['blogs']['view']) {
			$new_permissions['blogs']['view'] = false;
		}

		if (isset($_POST['blogs__create']) && !$users_default_permissions['blogs']['create']) {
			$new_permissions['blogs']['create'] = true;
		} elseif (!isset($_POST['blogs__create']) && $users_default_permissions['blogs']['create']) {
			$new_permissions['blogs']['create'] = false;
		}

		if (isset($_POST['blogs__manage']) && !$users_default_permissions['blogs']['manage']) {
			$new_permissions['blogs']['manage'] = true;
		} elseif (!isset($_POST['blogs__manage']) && $users_default_permissions['blogs']['manage']) {
			$new_permissions['blogs']['manage'] = false;
		}

		if (isset($_POST['users__view']) && !$users_default_permissions['users']['view']) {
			$new_permissions['users']['view'] = true;
		} elseif (!isset($_POST['users__view']) && $users_default_permissions['users']['view']) {
			$new_permissions['users']['view'] = false;
		}

		if (isset($_POST['users__upload_image']) && !$users_default_permissions['users']['upload_image']) {
			$new_permissions['users']['upload_image'] = true;
		} elseif (!isset($_POST['users__upload_image']) && $users_default_permissions['users']['upload_image']) {
			$new_permissions['users']['upload_image'] = false;
		}

		$extra['permissions'] = $new_permissions;

		DB::update([
			"update user_info",
			"set", [
				"usertype" => $_POST['user_type'],
				"extra" => json_encode($extra),
			],
			"where", [
				"username" => $user['username'],
			],
		]);

		dieWithJsonData(['status' => 'success', 'message' => '']);
	};
	$update_user_permissions_form->runAtServer();
}

$pageTitle = $user['username'] == Auth::id()
	? UOJLocale::get('modify my profile')
	: UOJLocale::get('modify his profile', $user['username'])
?>

<?php echoUOJPageHeader($pageTitle) ?>

<h1>
	<?= $pageTitle ?>
</h1>

<div class="row mt-4">
	<!-- left col -->
	<div class="col-md-3">

		<?= HTML::navListGroup($tabs_info, $cur_tab) ?>

		<a class="btn btn-light d-block mt-2 w-100 text-start text-primary" style="--bs-btn-hover-bg: #d3d4d570; --bs-btn-hover-border-color: transparent;" href="<?= HTML::url("/user/{$user['username']}") ?>">
			<i class="bi bi-arrow-left"></i> 返回
		</a>

		<?php if ($user['username'] != Auth::id()) : ?>
			<div class="alert alert-warning mt-3 small" role="alert">
				您正在使用管理特权查看并编辑其它用户的资料。
			</div>
		<?php endif ?>

	</div>
	<!-- end left col -->

	<!-- right col -->
	<div class="col-md-9">
		<?php if ($cur_tab == 'profile') : ?>
			<div class="card">
				<div class="card-body">
					<div id="result-alert" class="alert" role="alert" style="display: none"></div>
					<?php $update_profile_form->printHTML() ?>
				</div>
			</div>
		<?php elseif ($cur_tab == 'password') : ?>
			<div class="card">
				<div class="card-body">
					<div id="result-alert" class="alert" role="alert" style="display: none"></div>
					<form method="post" id="form-change_password">
						<div class="mb-3">
							<label for="input-current_password" class="form-label">
								<?= UOJLocale::get('current password') ?>
							</label>
							<input type="password" class="form-control" id="input-current_password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20">
							<div id="help-current_password" class="invalid-feedback"></div>
						</div>
						<div class="mb-3">
							<label for="input-new_password" class="form-label">
								<?= UOJLocale::get('new password') ?>
							</label>
							<input type="password" class="form-control" id="input-new_password" placeholder="<?= UOJLocale::get('enter your new password') ?>" maxlength="20">
							<div id="help-new_password" class="invalid-feedback"></div>
						</div>
						<div class="mb-3">
							<label for="input-confirm_password" class="form-label">
								<?= UOJLocale::get('confirm new password') ?>
							</label>
							<input type="password" class="form-control" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your new password') ?>" maxlength="20">
							<div id="help-confirm_password" class="invalid-feedback"></div>
						</div>
						<?php if (isSuperUser(Auth::user()) && $user['username'] != Auth::id()) : ?>
							<div class="alert alert-warning mb-0" role="alert">
								如需重置其他用户的密码，请前往 <a href="/super_manage/users" class="alert-link">系统管理</a> 页面操作。
							</div>
						<?php endif ?>

						<div class="text-center">
							<button type="submit" id="button-submit-change_password" name="submit-change_password" value="change_password" class="mt-3 btn btn-secondary">更新</button>
						</div>
					</form>
				</div>
			</div>
			<script>
				$('#form-change_password').submit(function() {
					var ok = true;

					$('#result-alert').hide();

					ok &= getFormErrorAndShowHelp('current_password', validatePassword);
					ok &= getFormErrorAndShowHelp('new_password', validateSettingPassword);

					if (ok) {
						$.ajax({
							method: 'POST',
							data: {
								'submit-change_password': 'change_password',
								'current_password': md5($('#input-current_password').val(), "<?= getPasswordClientSalt() ?>"),
								'new_password': md5($('#input-new_password').val(), "<?= getPasswordClientSalt() ?>"),
							},
							success: function(res) {
								if (res.status === 'success') {
									$('#result-alert')
										.html('密码修改成功！')
										.addClass('alert-success')
										.removeClass('alert-danger')
										.show();
								} else {
									$('#result-alert')
										.html('密码修改失败。' + (res.message || ''))
										.removeClass('alert-success')
										.addClass('alert-danger')
										.show();
								}

								$(window).scrollTop(0);
							},
							error: function() {
								$('#result-alert')
									.html('密码修改失败：请求失败。')
									.removeClass('alert-success')
									.addClass('alert-danger')
									.show();

								$(window).scrollTop(0);
							}
						});
					}

					return false;
				});
			</script>
		<?php elseif ($cur_tab == 'privilege') : ?>
			<div class="card">
				<div class="card-body">
					<div id="result-alert" class="alert" role="alert" style="display: none"></div>
					<p>关于各项权限的详细解释，请查看 <a href="https://s2oj.github.io/#/manage/permissions">权限管理</a> 文档。</p>
					<hr />
					<?php $update_user_permissions_form->printHTML() ?>
				</div>
			</div>
		<?php endif ?>
		<!-- end right col -->
	</div>
</div>

<?php echoUOJPageFooter() ?>
