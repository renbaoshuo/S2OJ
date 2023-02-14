<?php
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

Auth::check() || redirectToLogin();
UOJGroup::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJGroup::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$cur_tab = UOJRequest::get('tab', 'is_string', 'profile');

$tabs_info = [
	'profile' => [
		'name' => '基本信息',
		'url' => '/group/' . UOJGroup::info('id') . '/manage/profile',
	],
	'assignments' => [
		'name' => '作业管理',
		'url' => '/group/' . UOJGroup::info('id') . '/manage/assignments',
	],
	'users' => [
		'name' => '用户管理',
		'url' => '/group/' . UOJGroup::info('id') . '/manage/users',
	]
];

if (!isset($tabs_info[$cur_tab])) {
	UOJResponse::page404();
}

if ($cur_tab == 'profile') {
	$update_profile_form = new UOJForm('update_profile');
	$update_profile_form->addInput('name', [
		'label' => '名称',
		'default_value' => HTML::unescape(UOJGroup::info('title')),
		'validator_php' => function ($title, &$vdata) {
			if ($title == '') {
				return '名称不能为空';
			}

			if (strlen($title) > 100) {
				return '名称过长';
			}

			$title = HTML::escape($title);
			if ($title === '') {
				return '无效编码';
			}

			$vdata['title'] = $title;

			return '';
		},
	]);
	$update_profile_form->addCheckboxes('is_hidden', [
		'div_class' => 'mt-3',
		'label' => '可见性',
		'label_class' => 'me-3',
		'options' => [
			0 => '公开',
			1 => '隐藏',
		],
		'select_class' => 'd-inline-block',
		'option_div_class' => 'form-check d-inline-block ms-2',
		'default_value' => UOJGroup::info('is_hidden'),
	]);
	$update_profile_form->addTextArea('announcement', [
		'div_class' => 'mt-3',
		'label' => '公告',
		'input_class' => 'form-control font-monospace',
		'default_value' => UOJGroup::info('announcement'),
		'help' => '公告支持 Markdown 语法。',
		'validator_php' => function ($announcement, &$vdata) {
			if (strlen($announcement) > 3000) {
				return '公告过长';
			}

			$vdata['announcement'] = $announcement;

			return '';
		},
	]);
	$update_profile_form->handle = function ($vdata) {
		DB::update([
			"update `groups`",
			"set", [
				"title" => $vdata['title'],
				"is_hidden" => $_POST['is_hidden'],
				"announcement" => $vdata['announcement'],
			],
			"where", [
				"id" => UOJGroup::info('id'),
			],
		]);

		dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
	};
	$update_profile_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert')
					.html('小组信息修改成功！')
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert')
					.html('小组信息修改失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
	EOD);
	$update_profile_form->config['submit_button']['class'] = 'btn btn-secondary';
	$update_profile_form->config['submit_button']['text'] = '更新';
	$update_profile_form->runAtServer();
} elseif ($cur_tab == 'assignments') {
	if (isset($_POST['submit-remove_assignment']) && $_POST['submit-remove_assignment'] == 'remove_assignment') {
		$list_id = UOJRequest::post('list_id');

		$list = UOJGroupAssignment::query($list_id);
		if (!$list || !$list->valid()) {
			dieWithAlert('题单不合法。');
		}

		DB::delete([
			"delete from groups_assignments",
			"where", [
				"list_id" => $list->info['id'],
				"group_id" => UOJGroup::info('id'),
			],
		]);

		dieWithAlert('移除成功！');
	}

	$add_new_assignment_form = new UOJForm('add_new_assignment');
	$add_new_assignment_form->addInput('new_assignment_list_id', [
		'label' => '题单 ID',
		'validator_php' => function ($list_id, &$vdata) {
			if (!validateUInt($list_id)) {
				return '题单 ID 不合法';
			}

			$list = UOJList::query($list_id);

			if (!$list) {
				return '题单不存在';
			}

			if ($list->info['is_hidden']) {
				return '题单是隐藏的';
			}

			if (UOJGroup::cur()->hasAssignment($list)) {
				return '该题单已经在作业中';
			}

			$vdata['list_id'] = $list_id;

			return '';
		},
	]);
	$add_new_assignment_form->addInput('new_assignment_end_time', [
		'label' => '截止时间',
		'default_value' => UOJTime::time2str((new DateTime())->add(new DateInterval("P7D"))->setTime(22, 30, 0)),
		'validator_php' => function ($end_time, &$vdata) {
			try {
				$vdata['end_time'] = new DateTime($end_time);
			} catch (Exception $e) {
				return '无效时间格式';
			}

			return '';
		},
	]);
	$add_new_assignment_form->handle = function (&$vdata) {
		DB::insert([
			"insert into groups_assignments",
			DB::bracketed_fields(["group_id", "list_id", "end_time"]),
			"values", DB::tuple([
				UOJGroup::info('id'),
				$vdata['list_id'],
				$vdata['end_time']->format('Y-m-d H:i:s'),
			]),
		]);

		dieWithJsonData([
			'status' => 'success',
			'message' => '题单 #' . $vdata['list_id'] . ' 已经被添加到作业列表中，结束时间为 ' . $vdata['end_time']->format('Y-m-d H:i:s') . '。'
		]);
	};
	$add_new_assignment_form->config['submit_button']['class'] = 'btn btn-secondary';
	$add_new_assignment_form->config['submit_button']['text'] = '添加';
	$add_new_assignment_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert')
					.html('作业添加成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert')
					.html('作业添加失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
	EOD);
	$add_new_assignment_form->runAtServer();

	$hidden_time = new DateTime();
	$hidden_time->sub(new DateInterval('P3D'));
} elseif ($cur_tab == 'users') {
	if (isset($_POST['submit-remove_user']) && $_POST['submit-remove_user'] == 'remove_user') {
		$user = UOJUser::query(UOJRequest::post('remove_username'));

		if (!$user) {
			dieWithAlert('用户不存在。');
		}

		if (!UOJGroup::cur()->hasUser($user)) {
			dieWithAlert('该用户不在小组中。');
		}

		DB::delete([
			"delete from groups_users",
			"where", [
				"username" => $user['username'],
				"group_id" => UOJGroup::info('id'),
			],
		]);

		dieWithAlert('移除成功！');
	}

	$add_new_user_form = new UOJForm('add_new_user');
	$add_new_user_form->addInput('new_username', [
		'label' => '用户名',
		'validator_php' => function ($username, &$vdata) {
			$user = UOJUser::query($username);

			if (!$user) {
				return '用户不存在。';
			}

			if (UOJGroup::cur()->hasUser($user)) {
				return '该用户已经在小组中';
			}

			$vdata['username'] = $user['username'];

			return '';
		},
	]);
	$add_new_user_form->config['submit_button']['class'] = 'btn btn-secondary';
	$add_new_user_form->config['submit_button']['text'] = '添加';
	$add_new_user_form->handle = function (&$vdata) {
		DB::insert([
			"insert into groups_users",
			DB::bracketed_fields(["group_id", "username"]),
			"values",
			DB::tuple([
				UOJGroup::info('id'),
				$vdata['username']
			]),
		]);

		dieWithJsonData(['status' => 'success', 'message' => '已将用户名为 ' . $vdata['username'] . ' 的用户添加到本小组。']);
	};
	$add_new_user_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#result-alert')
					.html('用户添加成功！' + (res.message || ''))
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#result-alert')
					.html('用户添加失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			$(window).scrollTop(0);
		}
	EOD);
	$add_new_user_form->runAtServer();
}
?>
<?php echoUOJPageHeader('管理 - ' . UOJGroup::info('title')); ?>

<h1 class="d-block d-md-inline-block">
	<?= UOJGroup::info('title') ?>
	<small class="fs-5">(ID: #<?= UOJGroup::info('id') ?>)</small>
	管理
</h1>

<div class="row mt-4">
	<!-- left col -->
	<div class="col-md-3">
		<?= HTML::navListGroup($tabs_info, $cur_tab) ?>

		<?=
		UOJGroup::cur()->getLink([
			'class' => 'btn btn-light d-block mt-2 w-100 text-start text-primary uoj-back-btn',
			'text' => '<i class="bi bi-arrow-left"></i> 返回',
		]);
		?>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<div class="col-md-9">
		<?php if ($cur_tab == 'profile') : ?>
			<div class="card mt-3 mt-md-0">
				<div class="card-body">
					<div id="result-alert" class="alert" role="alert" style="display: none"></div>
					<div class="row row-cols-1 row-cols-md-2">
						<div class="col">
							<?= $update_profile_form->printHTML() ?>
						</div>
						<div class="col">
							<h5>注意事项</h5>
							<ul class="mb-0">
								<li>隐藏的小组无法被普通用户查看，即使该用户属于本小组。</li>
								<li>公告支持 Markdown 语法，但不支持添加数学公式。</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php elseif ($cur_tab == 'assignments') : ?>
			<div class="card mt-3 mt-md-0">
				<div class="card-header">
					<ul class="nav nav-tabs card-header-tabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" href="#assignments" data-bs-toggle="tab" data-bs-target="#assignments">作业列表</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#add-assignment" data-bs-toggle="tab" data-bs-target="#add-assignment">添加作业</a>
						</li>
					</ul>
				</div>
				<div class="card-body">
					<div class="tab-content">
						<div class="tab-pane active" id="assignments">
							<?php
							echoLongTable(
								['*'],
								'groups_assignments',
								["group_id" => UOJGroup::info('id')],
								'order by end_time desc, list_id desc',
								<<<EOD
									<tr>
										<th style="width:4em" class="text-center">题单 ID</th>
										<th style="width:12em">标题</th>
										<th style="width:4em">状态</th>
										<th style="width:8em">结束时间</th>
										<th style="width:8em">操作</th>
									</tr>
								EOD,
								function ($row) use ($hidden_time) {
									$assignment = UOJGroupAssignment::query($row['list_id']);

									echo HTML::tag_begin('tr');
									echo HTML::tag('td', ['class' => 'text-center'], $assignment->info['id']);
									echo HTML::tag_begin('td');
									echo $assignment->getLink();
									if ($assignment->info['is_hidden']) {
										echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
									}
									echo HTML::tag_end('td');
									if ($assignment->info['end_time'] < $hidden_time) {
										echo HTML::tag('td', ['class' => 'text-secondary'], '已隐藏');
									} elseif ($assignment->info['end_time'] < UOJTime::$time_now) {
										echo HTML::tag('td', ['class' => 'text-danger'], '已结束');
									} else {
										echo HTML::tag('td', ['class' => 'text-success'], '进行中');
									}
									echo HTML::tag('td', [], $assignment->info['end_time_str']);
									echo '<td>';
									echo ' <a class="text-decoration-none d-inline-block align-middle" href="/list/', $assignment->info['id'], '/manage">编辑</a> ';
									echo ' <form class="d-inline-block" method="POST" onsubmit=\'return confirm("你真的要移除这份作业（题单 #', $assignment->info['id'], '）吗？移除作业不会删除题单。")\'>'
										. HTML::hiddenToken()
										. '<input type="hidden" name="list_id" value="' . $assignment->info['id'] . '">'
										. '<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-remove_assignment" value="remove_assignment">移除</button>'
										. '</form>';
									echo '</td>';
									echo '</tr>';
								},
								[
									'page_len' => 10,
									'div_classes' => ['table-responsive'],
									'table_classes' => ['table', 'align-middle'],
								]
							);
							?>
						</div>
						<div class="tab-pane" id="add-assignment">
							<div id="result-alert" class="alert" role="alert" style="display: none"></div>
							<div class="row row-cols-1 row-cols-md-2">
								<div class="col">
									<?php $add_new_assignment_form->printHTML() ?>
								</div>
								<div class="col">
									<h5>注意事项</h5>
									<ul class="mt-0">
										<li>要被添加为作业的题单必须是公开的。</li>
										<li>请为学生预留合理的完成作业的时间。</li>
										<li>排行榜将在结束后停止更新。</li>
										<li>如需延长结束时间请删除后再次添加，排行数据不会丢失。</li>
										<li>作业结束七天后将会自动在小组主页中隐藏，但仍可直接通过 URL 访问。</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php elseif ($cur_tab == 'users') : ?>
			<div class="card mt-3 mt-md-0">
				<div class="card-header">
					<ul class="nav nav-tabs card-header-tabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" href="#users" data-bs-toggle="tab" data-bs-target="#users">用户列表</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#add-user" data-bs-toggle="tab" data-bs-target="#add-user">添加用户</a>
						</li>
					</ul>
				</div>
				<div class="card-body">
					<div class="tab-content">
						<div class="tab-pane active" id="users">
							<?php
							echoLongTable(
								['*'],
								'groups_users',
								["group_id" => UOJGroup::info('id')],
								'order by username asc',
								<<<EOD
									<tr>
										<th>用户名</th>
										<th>操作</th>
									</tr>
								EOD,
								function ($row) {
									echo HTML::tag_begin('tr');
									echo HTML::tag('td', [], UOJUser::getLink($row['username']));
									echo '<td>';
									echo '<form class="d-inline-block" method="POST" onsubmit=\'return confirm("你真的要从小组中移除这个用户吗？")\'>'
										. HTML::hiddenToken()
										. '<input type="hidden" name="remove_username" value="' . $row['username'] . '">'
										. '<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-remove_user" value="remove_user">移除</button>'
										. '</form>';
									echo '</td>';
									echo HTML::tag_end('tr');
								},
								[
									'page_len' => 10,
									'div_classes' => ['table-responsive'],
									'table_classes' => ['table', 'align-middle'],
								]
							);
							?>
						</div>
						<div class="tab-pane" id="add-user">
							<div id="result-alert" class="alert" role="alert" style="display: none"></div>
							<div class="row row-cols-1 row-cols-md-2">
								<div class="col">
									<?php $add_new_user_form->printHTML() ?>
								</div>
								<div class="col">
									<h5>注意事项</h5>
									<ul class="mb-0">
										<li>添加用户前请确认用户名是否正确以免带来不必要的麻烦。</li>
										<li>用户被添加到小组后将自动被加入组内的所有作业排行中。</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif ?>
	</div>
</div>

<?php echoUOJPageFooter() ?>
