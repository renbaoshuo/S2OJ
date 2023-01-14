<?php
requireLib('bootstrap5');
requireLib('md5');
requireLib('jquery.query');
requirePHPLib('form');
requirePHPLib('judger');

if (!Auth::check()) {
	redirectToLogin();
}

if (!isSuperUser(Auth::user())) {
	become403Page();
}

$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'index';

$tabs_info = [
	'index' => [
		'name' => '首页管理',
		'url' => "/super_manage/index",
	],
	'users' => [
		'name' => '用户管理',
		'url' => "/super_manage/users",
	],
	'submissions' => [
		'name' => '提交记录',
		'url' => "/super_manage/submissions",
	],
	'custom_test' => [
		'name' => '自定义测试',
		'url' => "/super_manage/custom_test",
	],
	'judger' => [
		'name' => '评测机管理',
		'url' => "/super_manage/judger",
	],
	'image_hosting' => [
		'name' => '图床管理',
		'url' => "/super_manage/image_hosting",
	],
];

if (!isset($tabs_info[$cur_tab])) {
	become404Page();
}

if ($cur_tab == 'index') {
	// ========== 公告 ==========
	if (UOJRequest::post('submit-delete_announcement') === 'delete_announcement') {
		crsf_defend();

		$blog_id = UOJRequest::post('blog_id');

		if (!validateUInt($blog_id)) {
			dieWithAlert('移除失败：博客 ID 无效');
		}

		DB::delete([
			"delete from important_blogs",
			"where", [
				"blog_id" => $blog_id,
			]
		]);

		dieWithAlert('移除成功！');
	}

	$announcements = DB::selectAll([
		"select", DB::fields([
			"id" => "blogs.id",
			"title" => "blogs.title",
			"poster" => "blogs.poster",
			"realname" => "user_info.realname",
			"post_time" => "blogs.post_time",
			"level" => "important_blogs.level",
			"is_hidden" => "blogs.is_hidden",
		]),
		"from blogs",
		"inner join important_blogs on important_blogs.blog_id = blogs.id",
		"inner join user_info on blogs.poster = user_info.username",
		"order by level desc, important_blogs.blog_id desc",
	]);

	$add_announcement_form = new UOJBs4Form('add_announcement');
	$add_announcement_form->addInput(
		'blog_id',
		'text',
		'博客 ID',
		'',
		function ($id, &$vdata) {
			if (!validateUInt($id)) {
				return '博客 ID 无效';
			}

			if (!queryBlog($id)) {
				return '博客不存在';
			}

			$vdata['blog_id'] = $id;

			return '';
		},
		null
	);
	$add_announcement_form->addInput(
		'blog_level',
		'text',
		'置顶级别',
		'0',
		function ($x, &$vdata) {
			if (!validateUInt($x)) {
				return '数字不合法';
			}

			if ($x > 3) {
				return '该级别不存在';
			}

			$vdata['level'] = $x;

			return '';
		},
		null
	);
	$add_announcement_form->handle = function (&$vdata) {
		$blog_id = $vdata['blog_id'];
		$blog_level = $vdata['level'];

		if (DB::selectExists([
			"select * from important_blogs",
			"where", [
				"blog_id" => $blog_id,
			]
		])) {
			DB::update([
				"update important_blogs",
				"set", [
					"level" => $blog_level,
				],
				"where", [
					"blog_id" => $blog_id,
				]
			]);
		} else {
			DB::insert([
				"insert into important_blogs",
				DB::bracketed_fields(["blog_id", "level"]),
				"values",
				DB::tuple([$blog_id, $blog_level]),
			]);
		}
	};
	$add_announcement_form->submit_button_config['align'] = 'compressed';
	$add_announcement_form->submit_button_config['text'] = '提交';
	$add_announcement_form->succ_href = '/super_manage/index#announcements';
	$add_announcement_form->runAtServer();

	// ========== 倒计时 ==========
	if (UOJRequest::post('submit-delete_countdown') === 'delete_countdown') {
		crsf_defend();

		$countdown_id = UOJRequest::post('countdown_id');

		if (!validateUInt($countdown_id)) {
			dieWithAlert('删除失败：倒计时 ID 无效');
		}

		DB::delete([
			"delete from countdowns",
			"where", [
				"id" => $countdown_id,
			]
		]);

		dieWithAlert('删除成功！');
	}

	$countdowns = DB::selectAll([
		"select", DB::fields(["id", "title", "end_time"]),
		"from countdowns",
		"order by end_time asc",
	]);

	$add_countdown_form = new UOJBs4Form('add_countdown');
	$add_countdown_form->addInput(
		'countdown_title',
		'text',
		'标题',
		'',
		function ($title, &$vdata) {
			if ($title == '') {
				return '标题不能为空';
			}

			$vdata['title'] = $title;

			return '';
		},
		null
	);
	$add_countdown_form->addInput(
		'countdown_end_time',
		'text',
		'结束时间',
		date("Y-m-d H:i:s"),
		function ($end_time, &$vdata) {
			try {
				$vdata['end_time'] = new DateTime($end_time);
			} catch (Exception $e) {
				return '无效时间格式';
			}

			return '';
		},
		null
	);
	$add_countdown_form->handle = function (&$vdata) {
		DB::insert([
			"insert into countdowns",
			DB::bracketed_fields(["title", "end_time"]),
			"values",
			DB::tuple([$vdata['title'], $vdata['end_time']->format('Y-m-d H:i:s')]),
		]);
	};
	$add_countdown_form->submit_button_config['align'] = 'compressed';
	$add_countdown_form->submit_button_config['text'] = '添加';
	$add_countdown_form->succ_href = '/super_manage/index#countdowns';
	$add_countdown_form->runAtServer();

	// ========== 常用链接 ==========
	if (UOJRequest::post('submit-delete_link') === 'delete_link') {
		crsf_defend();

		$link_id = UOJRequest::post('link_id');

		if (!validateUInt($link_id)) {
			dieWithAlert('删除失败：ID 无效');
		}

		DB::delete([
			"delete from friend_links",
			"where", [
				"id" => $link_id,
			]
		]);

		dieWithAlert('删除成功！');
	}

	$links = DB::selectAll([
		"select", DB::fields(["id", "title", "url", "level"]),
		"from friend_links",
		"order by level desc, id asc",
	]);

	$add_link_form = new UOJBs4Form('add_link');
	$add_link_form->addInput(
		'link_title',
		'text',
		'标题',
		'',
		function ($title, &$vdata) {
			if ($title == '') {
				return '标题不能为空';
			}

			$vdata['title'] = $title;

			return '';
		},
		null
	);
	$add_link_form->addInput(
		'link_url',
		'text',
		'链接',
		'',
		function ($url, &$vdata) {
			if (!validateURL($url)) {
				return '链接不合法';
			}

			$vdata['url'] = $url;

			return '';
		},
		null
	);
	$add_link_form->addInput(
		'link_level',
		'text',
		'权重',
		'10',
		function ($level, &$vdata) {
			if (!validateUInt($level)) {
				return '数字不合法';
			}

			$vdata['level'] = $level;

			return '';
		},
		null
	);
	$add_link_form->handle = function (&$vdata) {
		DB::insert([
			"insert into friend_links",
			DB::bracketed_fields(["title", "url", "level"]),
			"values",
			DB::tuple([$vdata['title'], $vdata['url'], $vdata['level']]),
		]);
	};
	$add_link_form->submit_button_config['align'] = 'compressed';
	$add_link_form->submit_button_config['text'] = '添加';
	$add_link_form->succ_href = '/super_manage/index#links';
	$add_link_form->runAtServer();
} elseif ($cur_tab == 'users') {
	$user_list_cond = [];

	if (isset($_GET['username']) && $_GET['username'] != "") {
		$user_list_cond[] = "username like '%" . DB::escape($_GET['username']) . "%'";
	}
	if (isset($_GET['usergroup']) && $_GET['usergroup'] != "") {
		$user_list_cond[] = "usergroup = '" . DB::escape($_GET['usergroup']) . "'";
	}

	if ($user_list_cond) {
		$user_list_cond = implode(' and ', $user_list_cond);
	} else {
		$user_list_cond = '1';
	}

	$register_form = new UOJBs4Form('register');
	$register_form->addVInput(
		'new_username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) {
			if (!validateUsername($username)) {
				return '用户名不合法';
			}

			if (UOJUser::query($username)) {
				return '该用户已存在';
			}

			$vdata['username'] = $username;

			return '';
		},
		null
	);
	$register_form->addVInput(
		'new_password',
		'password',
		'密码',
		'',
		function ($password, &$vdata) {
			$vdata['password'] = $password;

			return '';
		},
		'validatePassword'
	);
	$register_form->addVInput(
		'new_email',
		'text',
		'电子邮件（选填）',
		'',
		function ($email, &$vdata) {
			if ($email && !validateEmail($email)) {
				return '邮件地址不合法';
			}

			$vdata['email'] = $email;

			return '';
		},
		null
	);
	$register_form->addVInput(
		'new_realname',
		'text',
		'真实姓名（选填）',
		'',
		function ($realname, &$vdata) {
			$vdata['realname'] = $realname;

			return '';
		},
		null
	);
	$register_form->addVInput(
		'new_school',
		'text',
		'学校名称（选填）',
		'',
		function ($school, &$vdata) {
			$vdata['school'] = $school;

			return '';
		},
		null
	);
	$register_form->handle = function (&$vdata) {
		$user = [
			'username' => $vdata['username'],
			'realname' => $vdata['realname'],
			'school' => $vdata['school'],
			'email' => $vdata['email'],
			'password' => hash_hmac('md5', $vdata['password'], getPasswordClientSalt()),
		];

		UOJUser::register($user, ['check_email' => false]);

		dieWithJsonData(['status' => 'success', 'message' => '']);
	};
	$register_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert-register')
					.html('用户新建成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert-register')
					.html('用户新建失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
EOD);
	$register_form->runAtServer();

	$register_tmp_user_form = new UOJBs4Form('register_tmp_user');
	$register_tmp_user_form->addVInput(
		'new_tmp_username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) {
			if (!validateUsername($username)) {
				return '用户名不合法';
			}

			if (UOJUser::query($username)) {
				return '该用户已存在';
			}

			$vdata['username'] = $username;

			return '';
		},
		null
	);
	$register_tmp_user_form->addVInput(
		'new_tmp_password',
		'password',
		'密码',
		'',
		function ($password, &$vdata) {
			$vdata['password'] = $password;

			return '';
		},
		'validatePassword'
	);
	$register_tmp_user_form->addVInput(
		'new_tmp_email',
		'text',
		'电子邮件（选填）',
		'',
		function ($email, &$vdata) {
			if ($email && !validateEmail($email)) {
				return '邮件地址不合法';
			}

			$vdata['email'] = $email;

			return '';
		},
		null
	);
	$register_tmp_user_form->addVInput(
		'new_tmp_realname',
		'text',
		'真实姓名（选填）',
		'',
		function ($realname, &$vdata) {
			$vdata['realname'] = $realname;

			return '';
		},
		null
	);
	$register_tmp_user_form->addVInput(
		'new_tmp_school',
		'text',
		'学校名称（选填）',
		'',
		function ($school, &$vdata) {
			$vdata['school'] = $school;

			return '';
		},
		null
	);
	$register_tmp_user_form->addVInput(
		'new_tmp_expiration_time',
		'text',
		'过期时间',
		(new DateTime())->add(new DateInterval('P7D'))->format('Y-m-d H:i:s'),
		function ($str, &$vdata) {
			try {
				$vdata['expiration_time'] = new DateTime($str);
			} catch (Exception $e) {
				return '无效时间格式';
			}

			return '';
		},
		null
	);
	$register_tmp_user_form->handle = function (&$vdata) {
		$user = [
			'username' => $vdata['username'],
			'realname' => $vdata['realname'],
			'school' => $vdata['school'],
			'email' => $vdata['email'],
			'expiration_time' => $vdata['expiration_time']->format('Y-m-d H:i:s'),
			'password' => hash_hmac('md5', $vdata['password'], getPasswordClientSalt()),
		];

		UOJUser::registerTmpAccount($user, ['check_email' => false]);

		dieWithJsonData(['status' => 'success', 'message' => '']);
	};
	$register_tmp_user_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert-register_tmp')
					.html('临时用户新建成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert-register_tmp')
					.html('临时用户新建失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
EOD);
	$register_tmp_user_form->runAtServer();

	$change_password_form = new UOJBs4Form('change_password');
	$change_password_form->addVInput(
		'p_username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) {
			if (!validateUsername($username)) {
				return '用户名不合法';
			}

			if (!UOJUser::query($username)) {
				return '用户不存在';
			}

			$vdata['username'] = $username;

			return '';
		},
		null
	);
	$change_password_form->addVInput(
		'p_password',
		'password',
		'密码',
		'',
		function ($password, &$vdata) {
			$vdata['password'] = $password;

			return '';
		},
		'validatePassword'
	);
	$change_password_form->handle = function (&$vdata) {
		$password = hash_hmac('md5', $vdata['password'], getPasswordClientSalt());

		DB::update([
			"update user_info",
			"set", [
				"password" => getPasswordToStore($password, $vdata['username']),
			],
			"where", [
				"username" => $vdata['username'],
			],
		]);

		dieWithJsonData(['status' => 'success', 'message' => '用户 ' . $vdata['username'] . ' 的密码已经被成功重置。']);
	};
	$change_password_form->submit_button_config['class_str'] = 'btn btn-secondary mt-3';
	$change_password_form->submit_button_config['text'] = '重置';
	$change_password_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert-reset-password')
					.html('密码重置成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert-reset-password')
					.html('密码重置失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
EOD);
	$change_password_form->runAtServer();

	$change_usergroup_form = new UOJBs4Form('change_usergroup');
	$change_usergroup_form->addVInput(
		'username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) {
			if (!validateUsername($username)) {
				return '用户名不合法';
			}

			if (!UOJUser::query($username)) {
				return '用户不存在';
			}

			$vdata['username'] = $username;

			return '';
		},
		null
	);
	$change_usergroup_form->addVSelect('op_type', [
		'banneduser' => '设为封禁用户',
		'normaluser' => '设为普通用户',
		'superuser' => '设为超级用户',
	], '操作类型', '');
	$change_usergroup_form->handle = function ($vdata) {
		$username = $vdata['username'];
		$usergroup = '';

		switch ($_POST['op_type']) {
			case 'banneduser':
				DB::update("update user_info set usergroup = 'B', usertype = 'banned' where username = '{$username}'");
				$usergroup = '被封禁的用户';
				break;
			case 'normaluser':
				DB::update("update user_info set usergroup = 'U', usertype = 'student' where username = '{$username}'");
				$usergroup = '普通用户';
				break;
			case 'superuser':
				DB::update("update user_info set usergroup = 'S', usertype = 'student' where username = '{$username}'");
				$usergroup = '超级用户';
				break;
		}

		dieWithJsonData(['status' => 'success', 'message' => '用户 ' . $username . ' 现在是 ' . $usergroup . '。']);
	};
	$change_usergroup_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert-change_usergroup')
					.html('修改成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert-change_usergroup')
					.html('修改失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
EOD);
	$change_usergroup_form->runAtServer();

	$users_default_permissions = UOJContext::getMeta('users_default_permissions');
	$update_users_default_permissions_form = new UOJForm('update_users_default_permissions');
	$update_users_default_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5'], '题目'));
	$update_users_default_permissions_form->addCheckbox('problems__view', [
		'checked' => $users_default_permissions['problems']['view'],
		'label' => '查看题目',
		'role' => 'switch',
		'help' => '',
	]);
	$update_users_default_permissions_form->addCheckbox('problems__download_testdata', [
		'checked' => $users_default_permissions['problems']['download_testdata'],
		'label' => '下载测试数据',
		'role' => 'switch',
		'help' => '请谨慎开启此权限，以防数据泄露。',
	]);
	$update_users_default_permissions_form->addCheckbox('problems__create', [
		'checked' => $users_default_permissions['problems']['create'],
		'label' => '新建题目',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->addCheckbox('problems__manage', [
		'checked' => $users_default_permissions['problems']['manage'],
		'label' => '管理题目',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '比赛'));
	$update_users_default_permissions_form->addCheckbox('contests__view', [
		'checked' => $users_default_permissions['contests']['view'],
		'label' => '查看比赛',
		'role' => 'switch',
		'help' => '若用户不具有此权限，则只显示已报名过的比赛列表及详情。',
	]);
	$update_users_default_permissions_form->addCheckbox('contests__register', [
		'checked' => $users_default_permissions['contests']['register'],
		'label' => '报名比赛',
		'role' => 'switch',
		'help' => '',
	]);
	$update_users_default_permissions_form->addCheckbox('contests__create', [
		'checked' => $users_default_permissions['contests']['create'],
		'label' => '新建比赛',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->addCheckbox('contests__start_final_test', [
		'checked' => $users_default_permissions['contests']['start_final_test'],
		'label' => '开始比赛最终测试',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->addCheckbox('contests__manage', [
		'checked' => $users_default_permissions['contests']['manage'],
		'label' => '管理比赛',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '题单'));
	$update_users_default_permissions_form->addCheckbox('lists__view', [
		'checked' => $users_default_permissions['lists']['view'],
		'label' => '查看题单',
		'role' => 'switch',
		'help' => '',
	]);
	$update_users_default_permissions_form->addCheckbox('lists__create', [
		'checked' => $users_default_permissions['lists']['create'],
		'label' => '新建题单',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->addCheckbox('lists__manage', [
		'checked' => $users_default_permissions['lists']['manage'],
		'label' => '管理题单',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '小组'));
	$update_users_default_permissions_form->addCheckbox('groups__view', [
		'checked' => $users_default_permissions['groups']['view'],
		'label' => '查看小组',
		'role' => 'switch',
		'help' => '',
	]);
	$update_users_default_permissions_form->addCheckbox('groups__create', [
		'checked' => $users_default_permissions['groups']['create'],
		'label' => '新建小组',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->addCheckbox('groups__manage', [
		'checked' => $users_default_permissions['groups']['manage'],
		'label' => '管理小组',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '博客'));
	$update_users_default_permissions_form->addCheckbox('blogs__view', [
		'checked' => $users_default_permissions['blogs']['view'],
		'label' => '查看博客',
		'role' => 'switch',
		'help' => '',
	]);
	$update_users_default_permissions_form->addCheckbox('blogs__create', [
		'checked' => $users_default_permissions['blogs']['create'],
		'label' => '新建博客',
		'role' => 'switch',
		'help' => '',
	]);
	$update_users_default_permissions_form->addCheckbox('blogs__manage', [
		'checked' => $users_default_permissions['blogs']['manage'],
		'label' => '管理博客',
		'role' => 'switch',
		'help' => '',
		'disabled' => true,
	]);
	$update_users_default_permissions_form->appendHTML(HTML::tag('h3', ['class' => 'h5 mt-3'], '用户'));
	$update_users_default_permissions_form->addCheckbox('users__view', [
		'checked' => $users_default_permissions['users']['view'],
		'label' => '查看用户',
		'role' => 'switch',
		'help' => '若用户不具有此权限，则不能查看他人的个人资料。',
	]);
	$update_users_default_permissions_form->addCheckbox('users__upload_image', [
		'checked' => $users_default_permissions['users']['upload_image'],
		'label' => '上传图片',
		'role' => 'switch',
		'help' => '若用户不具有此权限，则不能使用图床功能。',
	]);
	$update_users_default_permissions_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert-update_users_default_permission')
					.html('修改成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert-update_users_default_permission')
					.html('修改失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
EOD);
	$update_users_default_permissions_form->config['confirm']['text'] = '你确定要修改所有用户的默认权限吗？';
	$update_users_default_permissions_form->handle = function () {
		$new_permissions = [
			'problems' => [
				'view' => isset($_POST['problems__view']),
				'download_testdata' => isset($_POST['problems__download_testdata']),
				'create' => false, // isset($_POST['problems__create']),
				'manage' => false, // isset($_POST['problems__manage']),
			],
			'contests' => [
				'view' => isset($_POST['contests__view']),
				'register' => isset($_POST['contests__register']),
				'create' => false, // isset($_POST['contests__create']),
				'start_final_test' => false, // isset($_POST['contests__start_final_test']),
				'manage' => false, // isset($_POST['contests__manage']),
			],
			'lists' => [
				'view' => isset($_POST['lists__view']),
				'create' => false, // isset($_POST['lists__create']),
				'manage' => false, // isset($_POST['lists__manage']),
			],
			'groups' => [
				'view' => isset($_POST['groups__view']),
				'create' => false, // isset($_POST['groups__create']),
				'manage' => false, // isset($_POST['groups__manage']),
			],
			'blogs' => [
				'view' => isset($_POST['blogs__view']),
				'create' => isset($_POST['blogs__create']),
				'manage' => false, // isset($_POST['blogs__manage']),
			],
			'users' => [
				'view' => isset($_POST['users__view']),
				'upload_image' => isset($_POST['users__upload_image']),
			],
		];

		UOJContext::setMeta('users_default_permissions', $new_permissions);

		dieWithJsonData(['status' => 'success', 'message' => '']);
	};
	$update_users_default_permissions_form->runAtServer();
} elseif ($cur_tab == 'submissions') {
} elseif ($cur_tab == 'custom_test') {
	requireLib('hljs');

	$submissions_pag = new Paginator([
		'col_names' => ['*'],
		'table_name' => 'custom_test_submissions',
		'cond' => '1',
		'tail' => 'order by id desc',
		'page_len' => 10
	]);

	$custom_test_deleter = new UOJBs4Form('custom_test_deleter');
	$custom_test_deleter->addInput(
		'last',
		'text',
		'删除末尾记录',
		'5',
		function ($x, &$vdata) {
			if (!validateUInt($x)) {
				return '不合法';
			}
			$vdata['last'] = $x;
			return '';
		},
		null
	);
	$custom_test_deleter->handle = function (&$vdata) {
		$all = DB::selectAll("select * from custom_test_submissions order by id asc limit {$vdata['last']}");
		foreach ($all as $submission) {
			$content = json_decode($submission['content'], true);
			unlink(UOJContext::storagePath() . $content['file_name']);
		}
		DB::delete("delete from custom_test_submissions order by id asc limit {$vdata['last']}");
	};
	$custom_test_deleter->submit_button_config['align'] = 'compressed';
	$custom_test_deleter->runAtServer();
} elseif ($cur_tab == 'judger') {
	$judger_adder = new UOJBs4Form('judger_adder');
	$judger_adder->addInput(
		'judger_adder_name',
		'text',
		'评测机名称',
		'',
		function ($x, &$vdata) {
			if (!validateUsername($x)) {
				return '不合法';
			}
			if (DB::selectCount("select count(*) from judger_info where judger_name='$x'") != 0) {
				return '不合法';
			}
			$vdata['name'] = $x;
			return '';
		},
		null
	);
	$judger_adder->handle = function (&$vdata) {
		$password = uojRandString(32);
		DB::insert("insert into judger_info (judger_name,password) values('{$vdata['name']}','{$password}')");
	};
	$judger_adder->submit_button_config['align'] = 'compressed';
	$judger_adder->runAtServer();

	$judger_deleter = new UOJBs4Form('judger_deleter');
	$judger_deleter->addInput(
		'judger_deleter_name',
		'text',
		'评测机名称',
		'',
		function ($x, &$vdata) {
			if (!validateUsername($x)) {
				return '不合法';
			}
			if (DB::selectCount("select count(*) from judger_info where judger_name='$x'") != 1) {
				return '不合法';
			}
			$vdata['name'] = $x;
			return '';
		},
		null
	);
	$judger_deleter->handle = function (&$vdata) {
		DB::delete("delete from judger_info where judger_name='{$vdata['name']}'");
	};
	$judger_deleter->submit_button_config['align'] = 'compressed';
	$judger_deleter->runAtServer();
} elseif ($cur_tab == 'image_hosting') {
	if (isset($_POST['submit-delete_image']) && $_POST['submit-delete_image'] == 'delete_image') {
		crsf_defend();

		$image_id = $_POST['image_id'];

		if (!validateUInt($image_id)) {
			dieWithAlert('删除失败：图片 ID 无效');
		}

		if (!($image = DB::selectFirst("SELECT * from users_images where id = $image_id"))) {
			dieWithAlert('删除失败：图片不存在');
		}

		unlink(UOJContext::storagePath() . $result['path']);
		DB::delete("DELETE FROM users_images WHERE id = $image_id");

		dieWithAlert('删除成功！');
	}

	$change_user_image_total_size_limit_form = new UOJBs4Form('change_user_image_total_size_limit');
	$change_user_image_total_size_limit_form->submit_button_config['align'] = 'compressed';
	$change_user_image_total_size_limit_form->addInput(
		'change_user_image_total_size_limit_username',
		'text',
		'用户名',
		'',
		function ($x, &$vdata) {
			if (!validateUsername($x)) {
				return '用户名不合法';
			}

			if (!UOJUser::query($x)) {
				return '用户不存在';
			}

			$vdata['username'] = $x;

			return '';
		},
		null
	);
	$change_user_image_total_size_limit_form->addInput(
		'change_user_image_total_size_limit_limit',
		'text',
		'存储限制（单位：Byte）',
		'104857600',
		function ($x, &$vdata) {
			if (!validateUInt($x, 10)) {
				return '限制不合法';
			}

			if (intval($x) > 2147483648) {
				return '限制不能大于 2 GB';
			}

			$vdata['limit'] = $x;

			return '';
		},
		null
	);
	$change_user_image_total_size_limit_form->handle = function (&$vdata) {
		DB::update([
			"update user_info",
			"set", [
				'extra' => DB::json_set(
					'extra',
					'$.image_hosting.total_size_limit',
					$vdata['limit'],
				),
			],
			"where", ["username" => $vdata['username']]
		]);
	};
	$change_user_image_total_size_limit_form->runAtServer();
}
?>

<?php echoUOJPageHeader(UOJLocale::get('system manage')) ?>

<h1>
	<?= UOJLocale::get('system manage') ?>
</h1>


<div class="row mt-4">
	<!-- left col -->
	<div class="col-md-3">
		<?= HTML::navListGroup($tabs_info, $cur_tab) ?>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<div class="col-md-9">
		<?php if ($cur_tab == 'index') : ?>
			<div class="card mt-3 mt-md-0">
				<div class="card-header">
					<ul class="nav nav-tabs card-header-tabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" href="#announcements" data-bs-toggle="tab" data-bs-target="#announcements">公告</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#countdowns" data-bs-toggle="tab" data-bs-target="#countdowns">倒计时</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#links" data-bs-toggle="tab" data-bs-target="#links">常用链接</a>
						</li>
					</ul>
				</div>
				<div class="card-body">
					<div class="tab-content">
						<!-- 公告 -->
						<div class="tab-pane active" id="announcements">
							<div id="announcements-list"></div>

							<script>
								var announcements = <?= json_encode($announcements) ?>;

								$('#announcements-list').long_table(
									announcements,
									1,
									'<tr>' +
									'<th style="width:3em">ID</th>' +
									'<th style="width:14em">标题</th>' +
									'<th style="width:8em">发布者</th>' +
									'<th style="width:8em">发布时间</th>' +
									'<th style="width:6em">置顶等级</th>' +
									'<th style="width:8em">操作</th>' +
									'</tr>',
									function(row) {
										var col_tr = '';

										col_tr += '<tr>';

										col_tr += '<td>' + row['id'] + '</td>';
										col_tr += '<td>' +
											(row['is_hidden'] == 1 ? '<span class="text-danger">[隐藏]</span> ' : '') +
											'<a class="text-decoration-none" href="/blogs/' + row['id'] + '">' +
											row['title'] +
											'</a>' +
											'</td>';
										col_tr += '<td>' + getUserLink(row['poster'], row['realname']) + '</td>';
										col_tr += '<td>' + row['post_time'] + '</td>';
										col_tr += '<td>' + row['level'] + '</td>';
										col_tr += '<td>' +
											'<a class="text-decoration-none d-inline-block align-middle" href="/post/' + row['id'] + '/write">编辑</a>' +
											'<form class="d-inline-block ms-2" method="POST" onsubmit=\'return confirm("你真的要移除这条公告吗？移除公告不会删除这篇博客。")\'>' +
											'<input type="hidden" name="_token" value="<?= crsf_token() ?>">' +
											'<input type="hidden" name="blog_id" value="' + row['id'] + '">' +
											'<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-delete_announcement" value="delete_announcement">移除</button>' +
											'</form>' +
											'</td>';

										col_tr += '</tr>';

										return col_tr;
									}, {
										div_classes: ['table-responsive'],
										table_classes: ['table', 'align-middle'],
										page_len: 20,
									}
								);
							</script>

							<h5>添加/修改公告</h5>
							<?php $add_announcement_form->printHTML(); ?>
						</div>

						<!-- 倒计时 -->
						<div class="tab-pane" id="countdowns">
							<div id="countdowns-list"></div>

							<script>
								var countdowns = <?= json_encode($countdowns) ?>;

								$('#countdowns-list').long_table(
									countdowns,
									1,
									'<tr>' +
									'<th style="width:14em">标题</th>' +
									'<th style="width:8em">结束时间</th>' +
									'<th style="width:6em">操作</th>' +
									'</tr>',
									function(row) {
										var col_tr = '';

										col_tr += '<tr>';

										col_tr += '<td>' + row['title'] + '</td>';
										col_tr += '<td>' + row['end_time'] + '</td>';
										col_tr += '<td>' +
											'<form method="POST" onsubmit=\'return confirm("你真的要删除这个倒计时吗？")\'>' +
											'<input type="hidden" name="_token" value="<?= crsf_token() ?>">' +
											'<input type="hidden" name="countdown_id" value="' + row['id'] + '">' +
											'<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-delete_countdown" value="delete_countdown">删除</button>' +
											'</form>' +
											'</td>';

										col_tr += '</tr>';

										return col_tr;
									}, {
										div_classes: ['table-responsive'],
										table_classes: ['table', 'align-middle'],
										page_len: 20,
									}
								);
							</script>

							<h5>添加倒计时</h5>
							<?php $add_countdown_form->printHTML(); ?>
						</div>

						<!-- 常用链接 -->
						<div class="tab-pane" id="links">
							<div id="links-list"></div>

							<script>
								var links = <?= json_encode($links) ?>;

								$('#links-list').long_table(
									links,
									1,
									'<tr>' +
									'<th style="width:16em">标题</th>' +
									'<th style="width:16em">链接</th>' +
									'<th style="width:6em">权重</th>' +
									'<th style="width:8em">操作</th>' +
									'</tr>',
									function(row) {
										var col_tr = '';

										col_tr += '<tr>';

										col_tr += '<td>' + row['title'] + '</td>';
										col_tr += '<td>' + row['url'] + '</td>';
										col_tr += '<td>' + row['level'] + '</td>';
										col_tr += '<td>' +
											'<form method="POST" onsubmit=\'return confirm("你真的要删除这条链接吗？")\'>' +
											'<input type="hidden" name="_token" value="<?= crsf_token() ?>">' +
											'<input type="hidden" name="link_id" value="' + row['id'] + '">' +
											'<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-delete_link" value="delete_link">删除</button>' +
											'</form>' +
											'</td>';

										col_tr += '</tr>';

										return col_tr;
									}, {
										div_classes: ['table-responsive'],
										table_classes: ['table', 'align-middle'],
										page_len: 20,
									}
								);
							</script>

							<h5>添加常用链接</h5>
							<?php $add_link_form->printHTML(); ?>
						</div>
					</div>
				</div>
			</div>

			<script>
				$(document).ready(function() {
					// Javascript to enable link to tab
					var hash = location.hash.replace(/^#/, '');
					if (hash) {
						bootstrap.Tab.jQueryInterface.call($('.nav-tabs a[href="#' + hash + '"]'), 'show').blur();
					}

					// Change hash for page-reload
					$('.nav-tabs a').on('shown.bs.tab', function(e) {
						window.location.hash = e.target.hash;
					});
				});
			</script>
		<?php elseif ($cur_tab == 'users') : ?>
			<div class="card mt-3 mt-md-0">
				<div class="card-header">
					<ul class="nav nav-tabs card-header-tabs">
						<li class="nav-item">
							<a class="nav-link active" href="#users" data-bs-toggle="tab" data-bs-target="#users">用户列表</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#new-user" data-bs-toggle="tab" data-bs-target="#new-user">新增用户</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#new-tmp-user" data-bs-toggle="tab" data-bs-target="#new-tmp-user">新增临时用户</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#reset-password" data-bs-toggle="tab" data-bs-target="#reset-password">重置密码</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#user-group" data-bs-toggle="tab" data-bs-target="#user-group">用户类别</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#users-default-permissions" data-bs-toggle="tab" data-bs-target="#users-default-permissions">默认权限</a>
						</li>
					</ul>
				</div>
				<div class="card-body">
					<div class="tab-content">
						<div class="tab-pane active" id="users">
							<form class="row gy-2 gx-3 align-items-end mb-3" target="_self" method="GET">
								<div class="col-auto">
									<label for="username" class="form-label">用户名</label>
									<input type="text" class="form-control" name="username" id="user-query-username" value="<?= HTML::escape(UOJRequest::get('username', null, '')) ?>" />
								</div>
								<div class="col-auto">
									<label for="user-query-usergroup" class="form-label">用户类别</label>
									<select class="form-select" id="user-query-usergroup" name="usergroup">
										<?php
										$usergroups = [
											'' => '*: 所有用户',
											'B' => 'B: 封禁用户',
											'T' => 'T: 临时用户',
											'U' => 'U: 普通用户',
											'S' => 'S: 超级用户',
										];
										?>
										<?php foreach ($usergroups as $name => $group) : ?>
											<option value="<?= $name ?>" <?php if ($_GET['usergroup'] == $name) : ?> selected <?php endif ?>><?= $group ?></option>
										<?php endforeach ?>
									</select>
								</div>
								<div class="col-auto">
									<button type="submit" id="user-query-submit" class="mt-2 btn btn-secondary">查询</button>
								</div>
							</form>
							<?php
							echoLongTable(
								['*'],
								'user_info',
								$user_list_cond,
								'order by username asc',
								<<<EOD
									<tr>
										<th>用户名</th>
										<th>学校</th>
										<th>用户类别</th>
										<th>注册时间</th>
										<th>过期时间</th>
										<th>操作</th>
									</tr>
								EOD,
								function ($row) {
									echo '<tr>';
									echo '<td>', UOJUser::getLink($row), '</td>';
									echo '<td>', HTML::escape($row['school']), '</td>';
									echo '<td>';
									switch ($row['usergroup']) {
										case 'S':
											echo UOJLocale::get('user::super user');
											break;
										case 'B':
											echo UOJLocale::get('user::banned user');
											break;
										case 'T':
											echo UOJLocale::get('user::tmp user');
											break;
										default:
											echo UOJLocale::get('user::normal user');
											break;
									}
									echo ', ', HTML::tag('small', ['class' => 'text-muted'], UOJLocale::get('user::' . $row['usertype']) ?: HTML::escape($row['usertype']));
									echo '</td>';
									echo '<td>', $row['register_time'], '</td>';
									echo '<td>', $row['expiration_time'], '</td>';
									echo '<td>', '<a class="text-decoration-none d-inline-block align-middle" href="/user/', $row['username'], '/edit">编辑</a>', '</td>';
									echo '</tr>';
								},
								[
									'page_len' => 20,
									'div_classes' => ['table-responsive'],
									'table_classes' => ['table', 'align-middle'],
								],
							);
							?>
						</div>
						<div class="tab-pane" id="new-user">
							<div id="result-alert-register" class="alert" role="alert" style="display: none"></div>
							<div class="row row-cols-1 row-cols-md-2">
								<div class="col">
									<?php $register_form->printHTML() ?>
								</div>
								<div class="col mt-3 mt-md-0">
									<h5>注意事项</h5>
									<ul class="mb-0">
										<li>用户名推荐格式为年级 + 姓名全拼，如 2022 级的张三同学可以设置为 <code>2022zhangsan</code>。对于外校学生，推荐格式为学校名称缩写 + 姓名拼音首字母，如山大附中的赵锦熙同学可以设置为 <code>sdfzzjx</code>)。</li>
										<li>请提醒用户及时修改初始密码，以免账号被盗导致教学资源流出。请勿设置过于简单的初始密码。</li>
										<li>我们推荐在创建账号时输入号主的电子邮件地址以便后期发生忘记密码等情况时进行验证。</li>
										<li>创建账号后可以在「修改个人信息」页面中的「特权」选项卡为用户分配权限。特别地，如果该用户是外校学生，那么您可能需要禁用其 <b>所有权限</b>，并为其手动报名比赛。</li>
										<li>对于外校学生，更推荐分发 <b>临时账号</b>。</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="new-tmp-user">
							<div id="result-alert-register_tmp" class="alert" role="alert" style="display: none"></div>
							<div class="row row-cols-1 row-cols-md-2">
								<div class="col">
									<?php $register_tmp_user_form->printHTML() ?>
								</div>
								<div class="col mt-3 mt-md-0">
									<h5>注意事项</h5>
									<ul class="mb-0">
										<li>用户名推荐格式为年级 + 姓名全拼，如 2022 级的张三同学可以设置为 <code>2022zhangsan</code>。对于外校学生，推荐格式为学校名称缩写 + 姓名拼音首字母，如山大附中的赵锦熙同学可以设置为 <code>sdfzzjx</code>)。</li>
										<li>请提醒用户及时修改初始密码，以免账号被盗导致教学资源流出。请勿设置过于简单的初始密码。</li>
										<li>我们推荐在创建账号时输入号主的电子邮件地址以便后期发生忘记密码等情况时进行验证。</li>
										<li>临时账号不具有任何权限，只能查看、参加已经用户报名了的比赛。创建账号后可以在「修改个人信息」页面中的「特权」选项卡为用户分配权限。特别地，如果该用户是外校学生，那么您可能需要禁用其 <b>所有权限</b>，并为其手动报名比赛。</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="reset-password">
							<div id="result-alert-reset-password" class="alert" role="alert" style="display: none"></div>
							<div class="row row-cols-1 row-cols-md-2">
								<div class="col">
									<?php $change_password_form->printHTML() ?>
								</div>
								<div class="col mt-3 mt-md-0">
									<h5>注意事项</h5>
									<ul class="mb-0">
										<li>在为用户重置密码前请核对对方身份以免被骗。</li>
										<li>请勿设置过于简单的密码。</li>
										<li>请提醒用户在登录后及时修改初始密码。</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="user-group">
							<div id="result-alert-change_usergroup" class="alert" role="alert" style="display: none"></div>
							<div class="row row-cols-1 row-cols-md-2">
								<div class="col">
									<?php $change_usergroup_form->printHTML() ?>
								</div>
								<div class="col mt-3 mt-md-0">
									<h5>注意事项</h5>
									<ul class="mb-0">
										<li>用户被封禁后将不能再次登录系统。</li>
										<li>将当前用户移除权限后将无法再次访问本页面。</li>
										<li>在修改用户类别前请仔细核对用户名以免产生不必要的麻烦。</li>
										<li>如需为用户设置题目上传者、题目管理员等权限，请前往对应用户的个人资料编辑页面，点击「特权」选项卡修改。</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="users-default-permissions">
							<div id="result-alert-update_users_default_permission" class="alert" role="alert" style="display: none"></div>
							<div class="row row-cols-1 row-cols-md-2">
								<div class="col">
									<?php $update_users_default_permissions_form->printHTML() ?>
								</div>
								<div class="col mt-3 mt-md-0">
									<h5>注意事项</h5>
									<ul class="mb-0">
										<li>此处修改的是 <b>所有用户</b> 的默认权限。</li>
										<li>如果某用户的 A 权限启闭状态与为该用户修改 A 权限时的默认权限状态不同，则在此处修改用户默认权限后该用户的 A 权限状态不会受到影响。</li>
										<li>对于每一个权限分类，若用户不具有新建项目权限，则只能对现有内容进行管理。</li>
										<li>如需为单个用户设置增加/移除特定权限，请前往对应用户的个人资料编辑页面，点击「特权」选项卡修改。</li>
										<li>出于安全考虑，部分权限不能被设置为默认权限。</li>
										<li>关于各项权限的详细解释，请查看 <a href="https://s2oj.github.io/#/manage/permissions">权限管理</a> 文档。</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<script>
				$(document).ready(function() {
					// Javascript to enable link to tab
					var hash = location.hash.replace(/^#/, '');
					if (hash) {
						bootstrap.Tab.jQueryInterface.call($('.nav-tabs a[href="#' + hash + '"]'), 'show').blur();
					}

					// Change hash for page-reload
					$('.nav-tabs a').on('shown.bs.tab', function(e) {
						window.location.hash = e.target.hash;
					});
				});
			</script>
		<?php elseif ($cur_tab === 'submissions') : ?>
			<h4>测评失败的提交记录</h4>
			<?php
			echoSubmissionsList(
				"result_error = 'Judgement Failed'",
				'order by id desc',
				[
					'result_hidden' => '',
					'table_config' => [
						'div_classes' => ['card', 'mb-3', 'table-responsive'],
						'table_classes' => ['table', 'uoj-table', 'mb-0', 'text-center']
					]
				],
				$myUser
			);
			?>
		<?php elseif ($cur_tab === 'custom_test') : ?>
			<div class="card mb-3 table-responsive">
				<table class="table uoj-table mb-0">
					<thead>
						<tr>
							<th class="text-center">ID</th>
							<th class="text-center">题目 ID</th>
							<th>提交者</th>
							<th>提交时间</th>
							<th>测评时间</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($submissions_pag->get() as $submission) : ?>
							<?php
							$problem = queryProblemBrief($submission['problem_id']);
							$submission_result = json_decode($submission['result'], true);
							?>
							<tr style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#custom_test__<?= $submission['id'] ?>">
								<td class="text-center text-primary">#<?= $submission['id'] ?></td>
								<td class="text-center">#<?= $submission['problem_id'] ?></td>
								<td><?= UOJUser::getLink($submission['submitter']) ?></td>
								<td><?= $submission['submit_time'] ?></td>
								<td><?= $submission['judge_time'] ?></td>
							</tr>
							<tr class="collapse" id="custom_test__<?= $submission['id'] ?>">
								<td colspan="233">
									<?php echoSubmissionContent($submission, getProblemCustomTestRequirement($problem)) ?>
									<?php echoCustomTestSubmissionDetails($submission_result['details'], "submission-{$submission['id']}-details") ?>
								</td>
							</tr>
						<?php endforeach ?>
						<?php if ($submissions_pag->isEmpty()) : ?>
							<tr>
								<td class="text-center" colspan="233">
									<?= UOJLocale::get('none') ?>
								</td>
							</tr>
						<?php endif ?>
					</tbody>
				</table>
			</div>
			<?= $submissions_pag->pagination() ?>

			<div class="card mt-3">
				<div class="card-body">
					<h5 class="card-title">删除末尾的 n 条记录</h5>
					<?php $custom_test_deleter->printHTML() ?>
				</div>
			</div>
		<?php elseif ($cur_tab == 'judger') : ?>
			<h3>评测机列表</h3>
			<?php
			echoLongTable(
				['*'],
				'judger_info',
				'1',
				'',
				<<<EOD
	<tr>
		<th>评测机名称</th>
		<th>密码</th>
		<th>IP</th>
	</tr>
EOD,
				function ($row) {
					echo <<<EOD
		<tr>
			<td>{$row['judger_name']}</td>
			<td>{$row['password']}</td>
			<td>{$row['ip']}</td>
		</tr>
EOD;
				},
				[
					'page_len' => 10,
					'div_classes' => ['card', 'mb-3', 'table-responsive'],
					'table_classes' => ['table', 'uoj-table', 'mb-0'],
				]
			); ?>

			<div class="card">
				<div class="card-body">
					<h5>添加评测机</h5>
					<?php $judger_adder->printHTML(); ?>
					<h5>删除评测机</h5>
					<?php $judger_deleter->printHTML(); ?>
				</div>
			</div>
		<?php elseif ($cur_tab == 'image_hosting') : ?>
			<?php
			echoLongTable(
				['*'],
				'users_images',
				'1',
				'order by id desc',
				<<<EOD
	<tr>
		<th style="width: 10em">上传者</th>
		<th style="width: 14em">预览</th>
		<th style="width: 6em">文件大小</th>
		<th style="width: 8em">上传时间</th>
		<th style="width: 6em">操作</th>
	</tr>
EOD,
				function ($row) {
					$user_link = UOJUser::getLink($row['uploader']);
					if ($row['size'] < 1024 * 512) {
						$size = strval(round($row['size'] * 1.0 / 1024, 1)) . ' KB';
					} else {
						$size = strval(round($row['size'] * 1.0 / 1024 / 1024, 1)) . ' MB';
					}
					$token = crsf_token();

					echo <<<EOD
	<tr>
		<td>$user_link</td>
		<td><img src="{$row['path']}" width="250" loading="lazy"></td>
		<td>$size</td>
		<td>{$row['upload_time']}</td>
		<td>
			<form class="d-inline-block" method="POST" onsubmit="return confirm('你真的要删除这张图片吗？删除后无法恢复。')">
				<input type="hidden" name="_token" value="$token">
				<input type="hidden" name="image_id" value="{$row['id']}">
				<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-delete_image" value="delete_image">删除</button>
			</form>
		</td>
	</tr>
EOD;
				},
				[
					'page_len' => 20,
					'div_classes' => ['card', 'mb-3', 'table-responsive'],
					'table_classes' => ['table', 'uoj-table', 'mb-0'],
				]
			); ?>
			<div class="card mt-3">
				<div class="card-body">
					<h5>修改用户图床空间上限</h5>
					<?php $change_user_image_total_size_limit_form->printHTML() ?>
				</div>
			</div>
		<?php endif ?>
	</div>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
