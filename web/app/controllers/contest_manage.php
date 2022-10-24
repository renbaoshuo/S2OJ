<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);
	
	if (!hasContestPermission($myUser, $contest)) {
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
			'url' => "/contest/{$contest['id']}/manage/profile",
		],
		'problems' => [
			'name' => '试题',
			'url' => "/contest/{$contest['id']}/manage/problems",
		],
		'managers' => [
			'name' => '管理者',
			'url' => "/contest/{$contest['id']}/manage/managers",
		],
		'others' => [
			'name' => '其他',
			'url' => "/contest/{$contest['id']}/manage/others",
		],
	];
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}
	
	if ($cur_tab == 'profile') {
		$profile_form = new UOJForm('time');
		$profile_form->addVInput(
			'name', 'text', '比赛标题', $contest['name'],
			function($name, &$vdata) {
				if ($name == '') {
					return '标题不能为空';
				}
				
				if (strlen($name) > 100) {
					return '标题过长';
				}
	
				$name = HTML::escape($name);
	
				if ($name === '') {
					return '无效编码';
				}
	
				$vdata['name'] = $name;
	
				return '';
			},
			null
		);
		$profile_form->addVInput(
			'start_time', 'text', '开始时间', $contest['start_time_str'],
			function($str, &$vdata) {
				try {
					$vdata['start_time'] = new DateTime($str);
				} catch (Exception $e) {
					return '无效时间格式';
				}
				return '';
			},
			null
		);
		$profile_form->addVInput(
			'last_min', 'text', '时长（单位：分钟）', $contest['last_min'],
			function($str, &$vdata) {
				if (!validateUInt($str)) {
					return '必须为一个整数';
				}

				$vdata['last_min'] = $str;
	
				return '';
			},
			null
		);
		$profile_form->handle = function(&$vdata) use ($contest) {
			$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
			$esc_name = DB::escape($vdata['name']);
			$esc_last_min = DB::escape($vdata['last_min']);
			
			DB::update("update contests set start_time = '$start_time_str', last_min = {$_POST['last_min']}, name = '$esc_name' where id = {$contest['id']}");

			dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
		};
		$profile_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#result-alert')
			.html('比赛信息修改成功！')
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#result-alert')
			.html('比赛信息修改失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$profile_form->runAtServer();
	} elseif ($cur_tab == 'problems') {
		if (isset($_POST['submit-remove_problem']) && $_POST['submit-remove_problem'] == 'remove_problem') {
			$problem_id = $_POST['problem_id'];

			if (!validateUInt($problem_id)) {
				dieWithAlert('无效的题目 ID。');
			}

			if (!queryProblemBrief($problem_id)) {
				dieWithAlert('题目不存在。');
			}

			if (!DB::selectFirst("SELECT * FROM contests_problems WHERE contest_id = {$contest['id']} AND problem_id = $problem_id")) {
				dieWithAlert('题目不在比赛中。');
			}

			DB::delete("DELETE FROM contests_problems WHERE contest_id = {$contest['id']} AND problem_id = $problem_id");
			
			unset($contest['extra_config']["problem_$problem_id"]);
			$esc_extra_config = DB::escape(json_encode($contest['extra_config']));
			DB::update("UPDATE `contests` SET extra_config = '$esc_extra_config' WHERE id = {$contest['id']}");

			dieWithAlert('移除成功！');
		}

		$add_problem_form = new UOJForm('add_problem');
		$add_problem_form->addVInput('problem_id', 'text', '题目 ID', '',
			function($problem_id, &$vdata) {
				if (!validateUInt($problem_id)) {
					return '无效的题目 ID。';
				}

				$problem = queryProblemBrief($problem_id);
				if (!$problem) {
					return '题目不存在。';
				}
				
				if (!hasProblemPermission(Auth::user(), $problem)) {
					return "无权添加题目 #$problem_id。";
				}

				$vdata['problem_id'] = $problem_id;
	
				return '';
			},
			null
		);
		$add_problem_form->addVSelect('judge_config', [
				'sample' => '只测样例',
				'no-details' => '测试全部数据，但不显示测试点详情',
				'full' => '测试全部数据',
			], '评测设置', 'sample');
		$add_problem_form->handle = function(&$vdata) use ($contest) {
			$dfn = DB::selectFirst("SELECT max(dfn) FROM contests_problems WHERE contest_id = {$contest['id']}")['max(dfn)'] + 1;
			DB::insert("INSERT INTO `contests_problems` (contest_id, problem_id, dfn) VALUES ({$contest['id']}, '{$vdata['problem_id']}', $dfn)");

			if ($_POST['judge_config'] != 'sample') {
				$contest['extra_config']["problem_$problem_id"] = $_POST['judge_config'];
				$esc_extra_config = DB::escape(json_encode($contest['extra_config']));
				DB::update("UPDATE `contests` SET extra_config = '$esc_extra_config' WHERE id = {$contest['id']}");
			}

			dieWithJsonData(['status' => 'success', 'message' => "题目 #{$vdata['problem_id']} 添加成功！"]);
		};
		$add_problem_form->submit_button_config['text'] = '添加';
		$add_problem_form->submit_button_config['margin_class'] = 'mt-3';
		$add_problem_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#result-alert')
			.html('添加成功！' + (res.message || ''))
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#result-alert')
			.html('添加失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$add_problem_form->runAtServer();
	} elseif ($cur_tab == 'managers') {
		if (isset($_POST['submit-remove_manager']) && $_POST['submit-remove_manager'] == 'remove_manager') {
			$username = $_POST['username'];

			if (!validateUsername($username)) {
				dieWithAlert('用户名不合法。');
			}

			if (!queryUser($username)) {
				dieWithAlert('用户不存在。');
			}

			if (!DB::selectFirst("SELECT * FROM contests_permissions WHERE contest_id = {$contest['id']} AND username = '$username'")) {
				dieWithAlert('用户不是这场比赛的管理员。');
			}

			DB::delete("DELETE FROM `contests_permissions` WHERE contest_id = ${contest['id']} AND username = '$username'");
			dieWithAlert('移除成功！');
		}

		$add_manager_form = new UOJForm('add_manager');
		$add_manager_form->addVInput('username', 'text', '用户名', '',
			function($username, &$vdata) {
				if (!validateUsername($username)) {
					return '用户名不合法';
				}

				if (!queryUser($username)) {
					return '用户不存在';
				}

				if (DB::selectFirst("SELECT * FROM contests_permissions WHERE contest_id = {$contest['id']} AND username = '$username'")) {
					return '用户已经是这场比赛的管理员';
				}

				$vdata['username'] = $username;

				return '';
			},
			null
		);
		$add_manager_form->handle = function(&$vdata) use ($contest) {
			DB::query("INSERT INTO `contests_permissions` (contest_id, username) VALUES (${contest['id']}, '{$vdata['username']}')");

			dieWithJsonData(['status' => 'success', 'message' => '已将用户名为 ' . $vdata['username'] . ' 的用户设置为本场比赛的管理者。']);
		};
		$add_manager_form->submit_button_config['text'] = '添加';
		$add_manager_form->submit_button_config['margin_class'] = 'mt-3';
		$add_manager_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#result-alert')
			.html('添加成功！' + (res.message || ''))
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#result-alert')
			.html('添加失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$add_manager_form->runAtServer();
	} elseif ($cur_tab == 'others') {
		$version_form = new UOJForm('version');
		$version_form->addVSelect('standings_version', [
				'1' => '1',
				'2' => '2',
			], '比赛排名版本', $contest['extra_config']['standings_version'] ?: '2');
		$version_form->handle = function() use ($contest) {
			$contest['extra_config']['standings_version'] = $_POST['standings_version'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("UPDATE contests SET extra_config = '$esc_extra_config' WHERE id = {$contest['id']}");

			dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
		};
		$version_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#version-result-alert')
			.html('修改成功！')
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#version-result-alert')
			.html('修改失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$version_form->runAtServer();

		$contest_type_form = new UOJForm('contest_type');
		$contest_type_form->addVSelect('contest_type', [
				'OI' => 'OI',
				'IOI' => 'IOI'
			], '比赛类型', $contest['extra_config']['contest_type'] ?: 'OI');
		$contest_type_form->handle = function() use ($contest) {
			$contest['extra_config']['contest_type'] = $_POST['contest_type'];
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("UPDATE contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");

			dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
		};
		$contest_type_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#type-result-alert')
			.html('修改成功！')
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#type-result-alert')
			.html('修改失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$contest_type_form->runAtServer();
		
		$blog_link_contests = new UOJForm('blog_link_contests');
		$blog_link_contests->addVInput('blog_id', 'text', '博客 ID', '',
			function ($blog_id, &$vdata) {
				if (!validateUInt($blog_id)) {
					return 'ID 不合法';
				}

				if (!queryBlog($blog_id)) {
					return '博客不存在';
				}

				$vdata['blog_id'] = $blog_id;
				
				return '';
			},
			null
		);
		$blog_link_contests->addVInput('title', 'text', '名称', '',
			function($title, &$vdata) {
				if ($title == '') {
					return '名称不能为空';
				}
				
				if (strlen($title) > 40) {
					return '名称过长';
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
		$blog_link_contests->addVSelect('op_type', [
				'add' => '添加',
				'del' => '移除',
			], '操作类型', '');
		$blog_link_contests->handle = function($vdata) use ($contest) {
			if ($_POST['op_type'] == 'add') {
				if (!isset($contest['extra_config']['links'])) {
					$contest['extra_config']['links'] = [];
				}

				$contest['extra_config']['links'][] = [$vdata['title'], $vdata['blog_id']];
			} elseif ($_POST['op_type'] == 'del') {
				$n = count($contest['extra_config']['links']);
				for ($i = 0; $i < $n; $i++) {
					if ($contest['extra_config']['links'][$i][1] == $vdata['blog_id']) {
						$contest['extra_config']['links'][$i] = $contest['extra_config']['links'][$n - 1];
						unset($contest['extra_config']['links'][$n - 1]);
						break;
					}
				}

				if (!count($contest['extra_config']['links'])) {
					unset($contest['extra_config']['links']);
				}
			}
	
			$esc_extra_config = json_encode($contest['extra_config']);
			$esc_extra_config = DB::escape($esc_extra_config);
			DB::update("UPDATE contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");

			dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
		};
		$blog_link_contests->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#blogs-result-alert')
			.html('操作成功！')
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#blogs-result-alert')
			.html('操作失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$blog_link_contests->runAtServer();

		$extra_registration_form = new UOJForm('extra_registration_form');
		$extra_registration_form->addVCheckboxes('extra_registration', [
			'0' => '禁止',
			'1' => '允许'
		], '是否允许额外报名', isset($contest['extra_config']['extra_registration']) ? $contest['extra_config']['extra_registration'] : '1');
		$extra_registration_form->handle = function() use ($contest) {
			$contest['extra_config']['extra_registration'] = $_POST['extra_registration'];
			$esc_extra_config = DB::escape(json_encode($contest['extra_config']));
			DB::update("UPDATE contests SET extra_config = '$esc_extra_config' WHERE id = {$contest['id']}");

			dieWithJsonData(['status' => 'success', 'message' => $_POST['extra_registration'] ? '已允许额外报名' : '已禁止额外报名']);
		};
		$extra_registration_form->setAjaxSubmit(<<<EOD
function(res) {
	if (res.status === 'success') {
		$('#extra-registration-result-alert')
			.html('操作成功！' + (res.message || ''))
			.addClass('alert-success')
			.removeClass('alert-danger')
			.show();
	} else {
		$('#extra-registration-result-alert')
			.html('操作失败。' + (res.message || ''))
			.removeClass('alert-success')
			.addClass('alert-danger')
			.show();
	}

	$(window).scrollTop(0);
}
EOD);
		$extra_registration_form->runAtServer();
	}

	?>
<?php echoUOJPageHeader(HTML::stripTags('比赛管理 - ' . $contest['name'])) ?>
<h1 class="h2">
	<?= $contest['name'] ?>
	<small class="fs-5">(ID: <?= $contest['id'] ?>)</small>
	管理
</h1>

<div class="row mt-4">
<!-- left col -->
<div class="col-md-3">

<?= HTML::navListGroup($tabs_info, $cur_tab) ?>

<a
	class="btn btn-light d-block mt-2 w-100 text-start text-primary"
	style="--bs-btn-hover-bg: #d3d4d570; --bs-btn-hover-border-color: transparent;"
	href="<?= HTML::url("/contest/{$contest['id']}") ?>">
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
			<?php $profile_form->printHTML(); ?>
		</div>
		<div class="col mt-3 mt-md-0">
			<h5>注意事项</h5>
			<ul class="mb-0">
				<li>请为选手预留合理的做题时间。一般而言，CSP 和 NOIP 的比赛时长为 4 小时，省选 / NOI 的比赛时长为 5 小时。</li>
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
					['*'],
					'contests_problems',
					"contest_id = '{$contest['id']}'",
					'ORDER BY dfn, problem_id',
					<<<EOD
	<tr>
		<th style="width:3em">ID</th>
		<th>标题</th>
		<th style="width:8em">评测设置</th>
		<th style="width:6em">操作</th>
	</tr>
EOD,
					function($row) {
						$problem = queryProblemBrief($row['problem_id']);
						echo '<tr>';
						echo '<td>', $row['problem_id'], '</td>';
						echo '<td>', getProblemLink($problem), '</td>';
						echo '<td>', isset($contest['extra_config']["problem_{$problem['id']}"]) ? $contest['extra_config']["problem_{$problem['id']}"] : 'sample', '</td>';
						echo '<td>';
						echo '<form class="d-inline-block" method="POST" target="_self" onsubmit=\'return confirm("你确定要将题目 #', $problem['id'], ' 从比赛中移除吗？")\'>';
						echo '<input type="hidden" name="_token" value="', crsf_token(),'">';
						echo '<input type="hidden" name="problem_id" value="', $problem['id'], '">';
						echo '<button type="submit" class="btn btn-link text-danger text-decoration-none p-0" name="submit-remove_problem" value="remove_problem">移除</button>';
						echo '</form>';
						echo '</td>';
						echo '</tr>';
					},
					[
						'echo_full' => true,
						'div_classes' => ['table-responsive'],
						'table_classes' => ['table', 'align-middle'],
					]
				)
	?>
		</div>
		<div class="tab-pane" id="add-problem">
			<div id="result-alert" class="alert" role="alert" style="display: none"></div>
			<div class="row row-cols-1 row-cols-md-2">
				<div class="col">
					<?php $add_problem_form->printHTML() ?>
				</div>
				<div class="col">
					<h5>注意事项</h5>
					<ul class="mt-0">
						<li>推荐在比赛结束前将题目设置为隐藏。</li>
						<li>对于「评测设置」选项，一般情况下保持默认（即只测样例）即可。</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
<?php elseif ($cur_tab == 'managers'): ?>
<div class="card mt-3 mt-md-0">
	<div class="card-header">
		<ul class="nav nav-tabs card-header-tabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" href="#managers" data-bs-toggle="tab" data-bs-target="#managers">管理者列表</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#add-manager" data-bs-toggle="tab" data-bs-target="#add-manager">添加管理者</a>
			</li>
		</ul>
	</div>
	<div class="card-body tab-content">
		<div class="tab-pane active" id="managers">
		<?php 
	echoLongTable(
		['*'],
		'contests_permissions',
		"contest_id = {$contest['id']}",
		'ORDER BY username',
		<<<EOD
				<tr>
					<th>用户名</th>
					<th style="width:6em">操作</th>
				</tr>
			EOD,
		function ($row) {
			echo '<tr>';
			echo '<td>', getUserLink($row['username']), '</td>';
			echo '<td>';
			echo '<form method="POST" target="_self" class="d-inline-block" onsubmit=\'return confirm("你确定要将 ', $row['username'], ' 从比赛管理员列表中移除吗？")\'>';
			echo '<input type="hidden" name="_token" value="', crsf_token(), '">';
			echo '<input type="hidden" name="username" value="', $row['username'], '">';
			echo '<button type="submit" class="btn btn-link text-danger text-decoration-none p-0" name="submit-remove_manager" value="remove_manager">移除</button>';
			echo '</form>';
			echo '</td>';
			echo '</tr>';
		},
		[
			'echo_full' => true,
			'div_classes' => ['table-responsive'],
			'table_classes' => ['table'],
		]
	);
	?>
	</div>
	<div class="tab-pane" id="add-manager">
		<div id="result-alert" class="alert" role="alert" style="display: none"></div>
		<div class="row row-cols-1 row-cols-md-2">
			<div class="col">
				<?php $add_manager_form->printHTML(); ?>
			</div>
			<div class="col mt-3 mt-md-0">
				<h5>注意事项</h5>
				<ul class="mb-0">
					<li>添加管理者前请确认用户名是否正确以免带来不必要的麻烦。</li>
					<li>比赛管理者如果报名了比赛仍可以正常参赛，但不报名比赛也可以查看并管理本场比赛。</li>
				</ul>
			</div>
		</div>
	</div>
</div>
</div>
<?php elseif ($cur_tab == 'others'): ?>
<div class="card mt-3 mt-md-0">
	<div class="card-header">
		<ul class="nav nav-tabs card-header-tabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" href="#type" data-bs-toggle="tab" data-bs-target="#type">赛制管理</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#standings-version" data-bs-toggle="tab" data-bs-target="#standings-version">排名版本</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#blogs" data-bs-toggle="tab" data-bs-target="#blogs">比赛资料</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#extra-registration" data-bs-toggle="tab" data-bs-target="#extra-registration">额外报名</a>
			</li>
		</ul>
	</div>
	<div class="card-body tab-content">
		<div class="tab-pane active" id="type">
			<div id="type-result-alert" class="alert" role="alert" style="display: none"></div>
			<div class="row row-cols-1 row-cols-md-2">
				<div class="col">
					<?php $contest_type_form->printHTML(); ?>
				</div>
				<div class="col mt-3 mt-md-0">
					<h5>注意事项</h5>
					<ul class="mb-0">
						<li>目前 S2OJ 支持 OI 和 IOI 赛制。</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="standings-version">
			<div id="version-result-alert" class="alert" role="alert" style="display: none"></div>
			<div class="row row-cols-1 row-cols-md-2">
				<div class="col">
					<?php $version_form->printHTML(); ?>
				</div>
				<div class="col mt-3 mt-md-0">
					<h5>注意事项</h5>
					<ul class="mb-0">
						<li>正常情况下无需调整此项设置。</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="blogs">
			<div id="blogs-result-alert" class="alert" role="alert" style="display: none"></div>
			<div class="row row-cols-1 row-cols-md-2">
				<div class="col">
					<?php $blog_link_contests->printHTML(); ?>
				</div>
				<div class="col mt-3 mt-md-0">
					<h5>注意事项</h5>
					<ul class="mb-0">
						<li>添加比赛资料前请确认博客是否处于公开状态。</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="extra-registration">
			<div id="extra-registration-result-alert" class="alert" role="alert" style="display: none"></div>
			<div class="row row-cols-1 row-cols-md-2">
				<div class="col">
					<?php $extra_registration_form->printHTML(); ?>
				</div>
				<div class="col mt-3 mt-md-0">
					<h5>注意事项</h5>
					<ul class="mb-0">
						<li>如果允许额外报名，则比赛开始后选手也可以报名参赛。</li>
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
