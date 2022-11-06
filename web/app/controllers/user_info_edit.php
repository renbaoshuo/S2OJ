<?php
requireLib('bootstrap5');
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
	$update_profile_form = new UOJBs4Form('update_profile');
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
		$update_profile_form->addVInput(
			'realname',
			'text',
			UOJLocale::get('user::real name'),
			$user['realname'],
			function ($realname, &$vdata) {
				$vdata['realname'] = $realname;

				return '';
			},
			null
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
	$update_profile_form->addVCheckboxes('avatar_source', [
		'gravatar' => 'Gravatar',
		'qq' => 'QQ',
	], UOJLocale::get('user::avatar source'), $extra['avatar_source'] ?: 'gravatar');
	$change_avatar_help = UOJLocale::get('change avatar help');
	$update_profile_form->appendHTML(<<<EOD
	<div style="margin-top: -1.25rem;" class="mb-3 small text-muted">
		$change_avatar_help
		</div>
EOD);
	$update_profile_form->addVInput(
		'email',
		'email',
		UOJLocale::get('email'),
		$user['email'] ?: '',
		function ($email, &$vdata) {
			if (!validateEmail($email)) {
				return 'Email 格式不合法。';
			}

			$vdata['email'] = $email;

			return '';
		},
		null
	);
	$update_profile_form->addVInput(
		'qq',
		'text',
		UOJLocale::get('QQ'),
		$user['qq'] == 0 ? '' : $user['qq'],
		function ($qq, &$vdata) {
			if ($qq && !validateQQ($qq)) {
				return 'QQ 格式不合法。';
			}

			$vdata['qq'] = $qq;

			return '';
		},
		null
	);
	$update_profile_form->addVInput(
		'github',
		'text',
		'GitHub',
		$extra['social']['github'] ?: '',
		function ($github, &$vdata) {
			if ($github && !validateGitHubUsername($github)) {
				return 'GitHub 用户名不合法。';
			}

			$vdata['github'] = $github;

			return '';
		},
		null
	);
	if (isSuperUser(Auth::user())) {
		$update_profile_form->addVInput(
			'school',
			'text',
			UOJLocale::get('school'),
			$user['school'] ?: '',
			function ($school, &$vdata) {
				$vdata['school'] = $school;

				return '';
			},
			null
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
	$update_profile_form->addVCheckboxes('sex', [
		'U' => UOJLocale::get('refuse to answer'),
		'M' => UOJLocale::get('male'),
		'F' => UOJLocale::get('female'),
	], UOJLocale::get('sex'), $user['sex']);
	$update_profile_form->addVInput(
		'motto',
		'text',
		UOJLocale::get('motto'),
		$user['motto'] ?: '',
		function ($motto, &$vdata) {
			if (!validateMotto($motto)) {
				return '格言格式不合法';
			}

			$vdata['motto'] = $motto;

			return '';
		},
		null
	);
	$update_profile_form->addVInput(
		'codeforces',
		'text',
		UOJLocale::get('codeforces handle'),
		$extra['social']['codeforces'] ?: '',
		function ($codeforces, &$vdata) {
			if ($codeforces && !validateUsername($codeforces)) {
				return 'Codeforces 用户名格式不合法。';
			}

			$vdata['codeforces'] = $codeforces;

			return '';
		},
		null
	);
	$update_profile_form->addVInput(
		'website',
		'text',
		UOJLocale::get('user::website'),
		$extra['social']['website'] ?: '',
		function ($url, &$vdata) {
			if ($url && !validateURL($url)) {
				return '链接格式不合法。';
			}

			$vdata['website'] = $url;

			return '';
		},
		null
	);
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
					$vdata['website']
				),
			],
			"where", ["username" => $user['username']]
		]);

		dieWithJsonData(['status' => 'success']);
	};
	$update_profile_form->submit_button_config['class_str'] = 'btn btn-secondary mt-3';
	$update_profile_form->submit_button_config['text'] = '更新';
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
				'password' => getPasswordToStore($new_password, $user['username']),
			],
			"where", ["username" => $user['username']]
		]);

		dieWithJsonData(['status' => 'success', 'message' => '密码修改成功']);
	}
} elseif ($cur_tab == 'privilege') {
	if (isset($_POST['submit-privilege']) && $_POST['submit-privilege'] == 'privilege' && isSuperUser(Auth::user())) {
		$user['usertype'] = 'student';

		if ($_POST['user_type'] == 'teacher') {
			addUserType($user, 'teacher');
			removeUserType($user, 'student');
		} else {
			addUserType($user, 'student');
		}

		if ($_POST['problem_uploader'] == 'yes') {
			addUserType($user, 'problem_uploader');
		}

		if ($_POST['problem_manager'] == 'yes') {
			addUserType($user, 'problem_manager');
		}

		if ($_POST['contest_judger'] == 'yes') {
			addUserType($user, 'contest_judger');
		}

		DB::update("UPDATE `user_info` SET `usertype` = '{$user['usertype']}' where `username` = '{$user['username']}'");

		dieWithJsonData(['status' => 'success', 'message' => '权限修改成功']);
	}
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
						<?php if (isSuperUser(Auth::user()) && $user['username'] != $myUser['username']) : ?>
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
					<form id="form-privilege" method="post">
						<?php if (isSuperUser(Auth::user())) : ?>
							<fieldset>
							<?php else : ?>
								<fieldset disabled>
								<?php endif ?>
								<div class="mb-3">
									<span>
										<?= UOJLocale::get('user::user group') ?>
									</span>
									<span class="d-inline-block ms-3">
										<?php if ($user['usergroup'] == 'S') : ?>
											<?= UOJLocale::get('user::super user') ?>
										<?php elseif ($user['usergroup'] == 'B') : ?>
											<?= UOJLocale::get('user::banned user') ?>
										<?php else : ?>
											<?= UOJLocale::get('user::normal user') ?>
										<?php endif ?>
									</span>
								</div>
								<div class="input-group mb-3">
									<label for="input-user_type" class="form-label">
										<?= UOJLocale::get('user::user type') ?>
									</label>
									<div class="form-check ms-3">
										<input class="form-check-input" type="radio" name="user_type" value="student" id="input-user_type" <?= hasUserType($user, 'student') && !hasUserType($user, 'teacher') ? 'checked' : '' ?>>
										<label class="form-check-label" for="input-user_type">
											<?= UOJLocale::get('user::student') ?>
										</label>
									</div>
									<div class="form-check ms-2">
										<input class="form-check-input" type="radio" name="user_type" value="teacher" id="input-user_type_2" <?= hasUserType($user, 'teacher') ? 'checked' : '' ?>>
										<label class="form-check-label" for="input-user_type_2">
											<?= UOJLocale::get('user::teacher') ?>
										</label>
									</div>
								</div>

								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" role="switch" name="problem_uploader" id="input-problem_uploader" <?= hasUserType($user, 'problem_uploader') ? 'checked' : '' ?>>
									<label class="form-check-label" for="input-problem_uploader">
										<?= UOJLocale::get('user::problem uploader') ?>
									</label>
								</div>

								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" role="switch" name="problem_manager" id="input-problem_manager" <?= hasUserType($user, 'problem_manager') ? 'checked' : '' ?>>
									<label class="form-check-label" for="input-problem_manager">
										<?= UOJLocale::get('user::problem manager') ?>
									</label>
								</div>

								<div class="form-check form-switch">
									<input class="form-check-input" type="checkbox" role="switch" name="contest_judger" id="input-contest_judger" <?= hasUserType($user, 'contest_judger') ? 'checked' : '' ?>>
									<label class="form-check-label" for="input-contest_judger">
										<?= UOJLocale::get('user::contest judger') ?>
									</label>
								</div>
								</fieldset>

								<?php if (isSuperUser(Auth::user())) : ?>
									<div class="text-center">
										<button type="submit" id="button-submit-privilege" name="submit-privilege" value="privilege" class="mt-3 btn btn-secondary">更新</button>
									</div>
								<?php endif ?>
					</form>
					<script>
						$('#form-privilege').submit(function(e) {
							$('#result-alert').hide();

							$.post('', {
								user_type: $('input[name=user_type]:checked').val(),
								problem_uploader: $('input[name=problem_uploader]').prop('checked') ? 'yes' : 'no',
								problem_manager: $('input[name=problem_manager]').prop('checked') ? 'yes' : 'no',
								contest_judger: $('input[name=contest_judger]').prop('checked') ? 'yes' : 'no',
								'submit-privilege': 'privilege',
							}, function(res) {
								if (res && res.status === 'success') {
									$('#result-alert')
										.html('权限修改成功！')
										.addClass('alert-success')
										.removeClass('alert-danger')
										.show();

									$(window).scrollTop(0);
								} else {
									$('#result-alert')
										.html('权限修改失败。' + (res.message || ''))
										.removeClass('alert-success')
										.addClass('alert-danger')
										.show();

									$(window).scrollTop(0);
								}
							});

							return false;
						});
					</script>
				</div>
			</div>
		<?php endif ?>
		<!-- end right col -->
	</div>
</div>

<?php echoUOJPageFooter() ?>
