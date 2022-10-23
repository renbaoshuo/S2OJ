<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	requireLib('bootstrap5');
	requirePHPLib('form');
	
	$list_id = $_GET['id'];

	if (!validateUInt($list_id) || !($list = queryProblemList($list_id))) {
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
			'url' => "/list/{$list['id']}/edit/profile",
		],
		'problems' => [
			'name' => '题目管理',
			'url' => "/list/{$list['id']}/edit/problems",
		],
		'assignments' => [
			'name' => '对应作业',
			'url' => "/list/{$list['id']}/edit/assignments",
		]
	];
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}

	if ($cur_tab == 'profile') {
		$list_tags = queryProblemListTags($list_id);
		
		$update_profile_form = new UOJForm('update_profile');
		$update_profile_form->addVInput('name', 'text', '标题', $list['title'],
			function($title, &$vdata) {
				if ($title == '') {
					return '标题不能为空';
				}
				
				if (strlen($title) > 100) {
					return '标题过长';
				}

				$title = HTML::escape($title);
				if ($title === '') {
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
			], '可见性', $list['is_hidden']);
		$update_profile_form->addVInput('tags', 'text', '标签（多个标签用逗号隔开）', implode(', ', $list_tags),
			function($tags_str, &$vdata) {
				$tags_str = str_replace('，', ',', $tags_str);
				$tags_raw = explode(',', $tags_str);
				$tags = [];

				if (count($tags_raw) > 10) {
					return '不能存在超过 10 个标签';
				}

				foreach ($tags_raw as $tag) {
					$tag = HTML::escape(trim($tag));

					if (strlen($tag) == 0) {
						continue;
					}

					if (strlen($tag) > 30) {
						return '标签 “' . $tag .'” 太长';
					}
					
					if (in_array($tag, $tags, true)) {
						return '标签 “' . $tag .'” 重复出现';
					}

					$tags[] = $tag;
				}

				$vdata['tags'] = $tags;

				return '';
			},
			null);
		$update_profile_form->addVTextArea('description', '描述', $list['description'],
			function($description, &$vdata) {
				if (strlen($description) > 3000) {
					return '描述过长';
				}

				$vdata['description'] = $description;

				return '';
			}, null);
		$update_profile_form->handle = function($vdata) use ($list, $list_tags) {
			$esc_title = DB::escape($vdata['title']);
			$is_hidden = $_POST['is_hidden'];
			$esc_description = DB::escape($vdata['description']);

			DB::update("UPDATE `lists` SET `title` = '$esc_title', `is_hidden` = '$is_hidden', `description` = '$esc_description' WHERE id = {$list['id']}");

			if ($vdata['tags'] !== $list_tags) {
				DB::delete("DELETE FROM `lists_tags` WHERE `list_id` = {$list['id']}");

				foreach ($vdata['tags'] as $tag) {
					$esc_tag = DB::escape($tag);

					DB::insert("INSERT INTO `lists_tags` (list_id, tag) VALUES ({$list['id']}, '$esc_tag')");
				}
			}

			dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
		};
		$update_profile_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#result-alert')
			.html('题单信息修改成功！')
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#result-alert')
			.html('题单信息修改失败。' + (res.message || ''))
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
	} elseif ($cur_tab == 'problems') {
		if (isset($_POST['submit-remove_problem']) && $_POST['submit-remove_problem'] == 'remove_problem') {
			crsf_defend();

			$problem_id = $_POST['problem_id'];

			if (!validateUInt($problem_id)) {
				dieWithAlert('题目 ID 不合法');
			}

			if (!queryProblemBrief($problem_id)) {
				dieWithAlert('题目不存在');
			}

			if (!queryProblemInList($list['id'], $problem_id)) {
				dieWithAlert('题目不在题单中');
			}

			DB::delete("DELETE FROM lists_problems WHERE problem_id = {$problem_id} AND list_id = {$list['id']}");

			dieWithAlert('移除成功！');
		}

		$n_problems = DB::selectCount("SELECT count(*) FROM `lists_problems` WHERE `list_id` = {$list['id']}");

		$add_new_problem_form = new UOJForm('add_new_problem');
		$add_new_problem_form->addVInput('problem_id', 'text', '题目 ID', '', 
			function ($problem_id, &$vdata) use ($list) {
				if (!validateUInt($problem_id)) {
					return 'ID 不合法';
				}

				if (!queryProblemBrief($problem_id)) {
					return '题目不存在';
				}

				if (queryProblemInList($list['id'], $problem_id)) {
					return '该题目已经在题单中';
				}

				$vdata['problem_id'] = $problem_id;
				
				return '';
			},
			null
		);
		$add_new_problem_form->submit_button_config['margin_class'] = 'mt-3';
		$add_new_problem_form->submit_button_config['text'] = '添加';
		$add_new_problem_form->handle = function($vdata) use ($list) {
			DB::insert("INSERT INTO `lists_problems` (`list_id`, `problem_id`) values ({$list['id']}, {$vdata['problem_id']})");

			dieWithJsonData(['status' => 'success', 'message' => '已将题目 #' . $vdata['problem_id'] . ' 添加到题单 #' . $list['id'] .' 中']);
		};
		$add_new_problem_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#result-alert')
			.html('题目添加成功！' + (res.message || ''))
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#result-alert')
			.html('题目添加失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$add_new_problem_form->runAtServer();
	} elseif ($cur_tab == 'assignments') {
		if (isset($_POST['submit-remove_assignment']) && $_POST['submit-remove_assignment'] == 'remove_assignment') {
			crsf_defend();

			$group_id = $_POST['group_id'];

			if (!validateUInt($group_id)) {
				dieWithAlert('小组 ID 不合法。');
			}

			if (!queryGroup($group_id)) {
				dieWithAlert('小组不存在。');
			}

			if (!queryAssignmentByGroupListID($group_id, $list['id'])) {
				dieWithAlert('该小组并未将本题单布置为作业。');
			}

			DB::delete("DELETE FROM groups_assignments WHERE group_id = {$group_id} AND list_id = {$list['id']}");

			dieWithAlert('移除成功！');
		}

		$add_new_assignment_form = new UOJForm('add_new_assignment');
		$add_new_assignment_form->addVInput('new_assignment_group_id', 'text', '小组 ID', '', 
			function ($group_id, &$vdata) use ($list) {
				if (!validateUInt($group_id)) {
					return '小组 ID 不合法';
				}

				if (!($list = queryGroup($group_id))) {
					return '小组不存在';
				}

				if ($list['is_hidden'] != 0) {
					return '题单是隐藏的';
				}

				if (queryAssignmentByGroupListID($group_id, $list['id'])) {
					return '该题单已经是这个小组的作业';
				}

				$vdata['group_id'] = $group_id;

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
		$add_new_assignment_form->handle = function(&$vdata) use ($list) {
			$esc_end_time = DB::escape($vdata['end_time']->format('Y-m-d H:i:s'));

			DB::insert("insert into groups_assignments (group_id, list_id, end_time) values ({$vdata['group_id']}, '{$list['id']}', '{$esc_end_time}')");

			dieWithJsonData([
				'status' => 'success',
				'message' => '题单 #' . $list['id'] . ' 已经被添加到小组 #' . $vdata['group_id'] . ' 的作业列表中，结束时间为 ' . $vdata['end_time']->format('Y-m-d H:i:s') . '。'
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
		
		$hidden_time = new DateTime();
		$hidden_time->sub(new DateInterval('P7D'));
	}
	?>

<?php echoUOJPageHeader('管理 - ' . $list['title']) ?>

<h1 class="h2">
	<?= $list['title'] ?>
	<small class="fs-5">(ID: #<?= $list['id'] ?>)</small>
	管理
</h1>

<div class="row mt-4">
<!-- left col -->
<div class="col-md-3">

<?= HTML::navListGroup($tabs_info, $cur_tab) ?>

<a
	class="btn btn-light d-block mt-2 w-100 text-start text-primary"
	style="--bs-btn-hover-bg: #d3d4d570; --bs-btn-hover-border-color: transparent;"
	href="<?= HTML::url("/list/{$list['id']}") ?>">
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
			<div class="col mt-3 mt-md-0">
				<h5>注意事项</h5>
				<ul class="mb-0">
					<li>隐藏的题单无法被普通用户查看。</li>
					<li>题单描述支持 Markdown 语法。</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php elseif ($cur_tab == 'problems'): ?>
<div class="card mt-3 mt-md-0">
	<div class="card-header">
		<ul class="nav nav-tabs card-header-tabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" href="#problems" data-bs-toggle="tab" data-bs-target="#problems">题目列表</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#add-problem" data-bs-toggle="tab" data-bs-target="#add-problem">添加题目</a>
			</li>
		</ul>
	</div>
	<div class="card-body tab-content">
		<div class="tab-pane active" id="problems">
			<?php
				echoLongTable(
					[
						'problems.id as id',
						'problems.title as title',
						'problems.is_hidden as is_hidden',
					],
					"problems inner join lists_problems on lists_problems.list_id = {$list['id']} and lists_problems.problem_id = problems.id",
					"1",
					'ORDER BY `id` ASC',
					<<<EOD
	<tr>
		<th class="text-center" style="width:5em">ID</th>
		<th>标题</th>
		<th style="width:4em">操作</th>
	</tr>
EOD,
					function ($row) {
						echo '<tr>';

						echo '<td class="text-center">', $row['id'], '</td>';
						echo '<td>', getProblemLink($row);
						if ($row['is_hidden']) {
							echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
						}
						echo '</td>';
						echo '<td>';
						echo '<form target="_self" method="POST" class="d-inline-block" onsubmit=\'return confirm("你确定要将题目 #', $row['id'],' 从题单中移除吗？")\'>';
						echo '<input type="hidden" name="_token" value="', crsf_token(), '">';
						echo '<input type="hidden" name="problem_id" value="', $row['id'], '">';
						echo '<button class="btn btn-link text-danger text-decoration-none p-0" name="submit-remove_problem" value="remove_problem">移除</button>';
						echo '</form>';
						echo '</td>';

						echo '</tr>';
					},
					[
						'page_len' => 20,
						'div_classes' => ['table-responsive'],
						'table_classes' => ['table', 'align-middle'],
						'print_after_table' => function() use ($n_problems) {
							echo '<div class="text-muted text-end">共 ', $n_problems,' 道题目</div>';
						},
					]
				);
	?>
		</div>
		<div class="tab-pane" id="add-problem">
			<div id="result-alert" class="alert" role="alert" style="display: none"></div>
			<div class="row row-cols-1 row-cols-md-2">
				<div class="col">
					<?php $add_new_problem_form->printHTML() ?>
				</div>
				<div class="col">
					<h5>注意事项</h5>
					<ul class="mt-0">
						<li>隐藏的题目添加进题单后无法被普通用户查看。</li>
						<li>如当前题单已经被设置为某个小组的作业，则作业也会一并更新。</li>
					</ul>
				</div>
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
			echoLongTable(
				['*'],
				'groups_assignments',
				"list_id = {$list['id']}",
				'order by end_time desc, group_id asc',
				<<<EOD
	<tr>
		<th style="width:4em" class="text-center">小组 ID</th>
		<th style="width:12em">名称</th>
		<th style="width:4em">状态</th>
		<th style="width:8em">结束时间</th>
		<th style="width:8em">操作</th>
	</tr>
EOD,
				function($row) use ($list, $hidden_time) {
					$group = queryGroup($row['group_id']);
					$end_time = DateTime::createFromFormat('Y-m-d H:i:s', $row['end_time']);

					echo '<tr>';
					echo '<td class="text-center">', $group['id'], '</td>';
					echo '<td>', '<a class="text-decoration-none" href="/group/', $group['id'], '">', HTML::escape($group['title']), '</a>', '</td>';
					if ($end_time < $hidden_time) {
						echo '<td class="text-secondary">已隐藏</td>';
					} elseif ($end_time < UOJTime::$time_now) {
						echo '<td class="text-danger">已结束</td>';
					} else {
						echo '<td class="text-success">进行中</td>';
					}
					echo '<td>', $end_time->format('Y-m-d H:i:s'), '</td>';
					echo '<td>';
					echo ' <a class="text-decoration-none d-inline-block align-middle" href="/group/', $group['id'], '/assignment/', $list['id'],'">排行榜</a> ';
					echo ' <form class="d-inline-block" method="POST" onsubmit=\'return confirm("你真的要为小组 #', $group['id'], ' 移除这份作业吗？移除作业不会删除题单。")\'>'
							. '<input type="hidden" name="_token" value="' . crsf_token() . '">'
							. '<input type="hidden" name="group_id" value="' . $group['id'] . '">'
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
							<li>请为学生预留合理的完成作业的时间。</li>
							<li>排行榜将在作业结束后停止更新。</li>
							<li>如需延长结束时间请删除后再次添加，排行数据不会丢失。</li>
							<li>作业结束七天后将会自动在小组主页中隐藏，但仍可直接通过 URL 访问。</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif ?>
</div>
<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
