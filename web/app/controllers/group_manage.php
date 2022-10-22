<?php
	if (!Auth::check()) {
		redirectToLogin();
	}
	
	requireLib('bootstrap5');
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	$group_id = $_GET['id'];
	if (!validateUInt($group_id) || !($group = queryGroup($group_id))) {
		become404Page();
	}

	if (!isSuperUser($myUser)) {
		become403Page();
	}

	if (isset($_GET['tab'])) {
		$cur_tab = $_GET['tab'];
	} else {
		$cur_tab = 'profile';
	}
	
	$tabs_info = [
		'profile' => [
			'name' => '基本信息',
			'url' => "/group/{$group['id']}/manage/profile",
		],
		'assignments' => [
			'name' => '作业管理',
			'url' => "/group/{$group['id']}/manage/assignments",
		],
		'users' => [
			'name' => '用户管理',
			'url' => "/group/{$group['id']}/manage/users",
		]
	];
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}

	if ($cur_tab == 'profile') {
		$update_profile_form = new UOJForm('update_profile');
		$update_profile_form->addVInput('name', 'text', '名称', $group['title'],
			function($title, &$vdata) {
				if ($title == '') {
					return '名称不能为空';
				}
				
				if (strlen($title) > 100) {
					return '名称过长';
				}

				if (HTML::escape($title) === '') {
					return '无效编码';
				}

				$vdata['title'] = $title;

				return '';
			},
			null
		);
		$update_profile_form->addVCheckboxes('is_hidden', [
				'0' => '公开',
				'1' => '隐藏',
			], '可见性', $group['is_hidden']);
		$update_profile_form->addVTextArea('announcement', '公告', $group['announcement'],
			function($announcement, &$vdata) {
				if (strlen($announcement) > 3000) {
					return '公告过长';
				}

				$vdata['announcement'] = $announcement;

				return '';
			}, null);
		$update_profile_form->handle = function($vdata) use ($group) {
			$esc_title = DB::escape($vdata['title']);
			$is_hidden = $_POST['is_hidden'];
			$esc_announcement = $vdata['announcement'];

			DB::update("UPDATE `groups` SET title = '$esc_title', is_hidden = '$is_hidden', announcement = '$esc_announcement' WHERE id = {$group['id']}");

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
		$update_profile_form->submit_button_config['margin_class'] = 'mt-3';
		$update_profile_form->submit_button_config['text'] = '更新';
		$update_profile_form->runAtServer();
	} elseif ($cur_tab == 'assignments') {
		if (isset($_POST['submit-remove_assignment']) && $_POST['submit-remove_assignment'] == 'remove_assignment') {
			$list_id = $_POST['list_id'];

			if (!validateUInt($list_id)) {
				dieWithAlert('题单 ID 不合法。');
			}

			if (!queryAssignmentByGroupListID($group['id'], $list_id)) {
				dieWithAlert('该题单不在作业中。');
			}

			DB::delete("DELETE FROM `groups_assignments` WHERE `list_id` = $list_id AND `group_id` = {$group['id']}");

			dieWithAlert('移除成功！');
		}

		$add_new_assignment_form = new UOJForm('add_new_assignment');
		$add_new_assignment_form->addVInput('new_assignment_list_id', 'text', '题单 ID', '', 
			function ($list_id, &$vdata) use ($group) {
				if (!validateUInt($list_id)) {
					return '题单 ID 不合法';
				}

				if (!($list = queryProblemList($list_id))) {
					return '题单不存在';
				}

				if ($list['is_hidden'] != 0) {
					return '题单是隐藏的';
				}

				if (queryAssignmentByGroupListID($group['id'], $list_id)) {
					return '该题单已经在作业中';
				}

				$vdata['list_id'] = $list_id;

				return '';
			},
			null
		);
		$default_end_time = new DateTime();
		$default_end_time->setTime(22, 30, 0);
		$default_end_time->add(new DateInterval("P7D"));
		$add_new_assignment_form->addVInput('new_assignment_end_time', 'text', '截止时间', $default_end_time->format('Y-m-d H:i'), 
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
		$add_new_assignment_form->handle = function(&$vdata) use ($group) {
			$esc_end_time = DB::escape($vdata['end_time']->format('Y-m-d H:i:s'));

			DB::insert("insert into groups_assignments (group_id, list_id, end_time) values ({$group['id']}, '{$vdata['list_id']}', '{$esc_end_time}')");

			dieWithJsonData([
				'status' => 'success',
				'message' => '题单 #' . $vdata['list_id'] . ' 已经被添加到作业列表中，结束时间为 ' . $vdata['end_time']->format('Y-m-d H:i:s') . '。'
			]);
		};
		$add_new_assignment_form->submit_button_config['margin_class'] = 'mt-3';
		$add_new_assignment_form->submit_button_config['text'] = '添加';
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
	} elseif ($cur_tab == 'users') {
		if (isset($_POST['submit-remove_user']) && $_POST['submit-remove_user'] == 'remove_user') {
			$username = $_POST['remove_username'];

			if (!validateUsername($username)) {
				dieWithAlert('用户名不合法。');
			}

			if (!queryUser($username)) {
				dieWithAlert('用户不存在。');
			}

			if (!queryUserInGroup($group['id'], $username)) {
				dieWithAlert('该用户不在小组中。');
			}

			DB::delete("DELETE FROM `groups_users` WHERE `username` = '$username' AND `group_id` = {$group['id']}");

			dieWithAlert('移除成功！');
		}

		$add_new_user_form = new UOJForm('add_new_user');
		$add_new_user_form->addVInput('new_username', 'text', '用户名', '', 
			function ($username, &$vdata) {
				global $group_id;

				if (!validateUsername($username)) {
					return '用户名不合法';
				}

				if (!queryUser($username)) {
					return '用户不存在';
				}

				if (queryUserInGroup($group_id, $username)) {
					return '该用户已经在小组中';
				}

				$vdata['username'] = $username;

				return '';
			},
			null
		);
		$add_new_user_form->submit_button_config['margin_class'] = 'mt-3';
		$add_new_user_form->submit_button_config['text'] = '添加';
		$add_new_user_form->handle = function(&$vdata) use ($group) {
			DB::insert("insert into groups_users (group_id, username) values ({$group['id']}, '{$vdata['username']}')");

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
<?php echoUOJPageHeader('管理 - ' . $group['title']); ?>

<h1 class="h2 d-block d-md-inline-block">
	<?= $group['title'] ?>
	<small class="fs-5">(ID: #<?= $group['id'] ?>)</small>
	管理
</h1>

<div class="row mt-4">
<!-- left col -->
<div class="col-md-3">

<?= HTML::navListGroup($tabs_info, $cur_tab) ?>

<a
	class="btn btn-light d-block mt-2 w-100 text-start text-primary"
	style="--bs-btn-hover-bg: #d3d4d570; --bs-btn-hover-border-color: transparent;"
	href="<?= HTML::url("/group/{$group['id']}") ?>">
	<i class="bi bi-arrow-left"></i> 返回
</a>

</div>
<!-- end left col -->

<!-- right col -->
<div class="col-md-9">
<?php if ($cur_tab == 'profile'): ?>
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
<?php elseif ($cur_tab == 'assignments'): ?>
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
						$now = new DateTime();
	$hidden_time = new DateTime();
	$hidden_time->sub(new DateInterval('P7D'));
	echoLongTable(
		['*'],
		'groups_assignments',
		"group_id = {$group['id']}",
		'order by end_time desc, list_id desc',
		<<<EOD
	<tr>
		<th style="width:3em" class="text-center">ID</th>
		<th style="width:12em">标题</th>
		<th style="width:4em">状态</th>
		<th style="width:8em">结束时间</th>
		<th style="width:8em">操作</th>
	</tr>
EOD,
		function($row) use ($group, $now, $hidden_time) {
			$list = queryProblemList($row['list_id']);
			$end_time = DateTime::createFromFormat('Y-m-d H:i:s', $row['end_time']);

			echo '<tr>';
			echo '<td class="text-center">', $list['id'], '</td>';
			echo '<td>', '<a class="text-decoration-none" href="/group/', $group['id'], '/assignment/', $list['id'],'">', HTML::escape($list['title']), '</a>', '</td>';
			if ($end_time < $hidden_time) {
				echo '<td class="text-secondary">已隐藏</td>';
			} elseif ($end_time < $now) {
				echo '<td class="text-danger">已结束</td>';
			} else {
				echo '<td class="text-success">进行中</td>';
			}
			echo '<td>', $end_time->format('Y-m-d H:i:s'), '</td>';
			echo '<td>';
			echo '<a class="text-decoration-none d-inline-block align-middle" href="/problem_list/', $list['id'], '/manage">编辑</a> ';
			echo ' <form class="d-inline-block" method="POST" onsubmit=\'return confirm("你真的要移除这份作业吗？移除作业不会删除题单。")\'>'
					. '<input type="hidden" name="_token" value="' . crsf_token() . '">'
					. '<input type="hidden" name="list_id" value="' . $list['id'] . '">'
					. '<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-remove_assignment" value="remove_assignment">移除</button>'
				. '</form>';
			echo '</td>';
			echo '</tr>';
		},
		[
			'page_len' => 20,
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
<?php elseif ($cur_tab == 'users'): ?>
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
					"group_id = {$group['id']}",
					'order by username asc',
					<<<EOD
				<tr>
					<th>用户名</th>
					<th>操作</th>
				</tr>
			EOD,
					function($row) use ($group) {
						echo '<tr>';
						echo '<td>', getUserLink($row['username']), '</td>';
						echo '<td>';
						echo '<form class="d-inline-block" method="POST" onsubmit=\'return confirm("你真的要从小组中移除这个用户吗？")\'>'
								. '<input type="hidden" name="_token" value="' . crsf_token() . '">'
								. '<input type="hidden" name="remove_username" value="' . $row['username'] . '">'
								. '<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-remove_user" value="remove_user">移除</button>'
							. '</form>';
						echo '</td>';
						echo '</tr>';
					},
					[
						'page_len' => 20,
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
