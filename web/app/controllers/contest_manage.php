<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
isSuperUser(Auth::user()) || UOJResponse::page403();
$contest = UOJContest::info();

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
	$profile_form = new UOJBs4Form('time');
	$profile_form->addVInput(
		'name',
		'text',
		'比赛标题',
		$contest['name'],
		function ($name, &$vdata) {
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
		'start_time',
		'text',
		'开始时间',
		$contest['start_time_str'],
		function ($str, &$vdata) {
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
		'last_min',
		'text',
		'时长（单位：分钟）',
		$contest['last_min'],
		function ($str, &$vdata) {
			if (!validateUInt($str)) {
				return '必须为一个整数';
			}

			$vdata['last_min'] = $str;

			return '';
		},
		null
	);
	$profile_form->handle = function (&$vdata) use ($contest) {
		DB::update([
			"update contests",
			"set", [
				"name" => $vdata['name'],
				"start_time" => $vdata['start_time']->format('Y-m-d H:i:s'),
				"last_min" => $vdata['last_min'],
			], "where", ["id" => $contest['id']]
		]);

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
		$problem = UOJProblem::query($problem_id);

		if (!$problem) {
			dieWithAlert('题目不存在');
		}

		if (!UOJContest::cur()->hasProblem($problem)) {
			dieWithAlert('题目不在本场比赛中');
		}

		DB::delete([
			"delete from contests_problems",
			"where", [
				"contest_id" => $contest['id'],
				"problem_id" => $problem_id,
			],
		]);

		unset($contest['extra_config']["problem_$problem_id"]);
		unset($contest['extra_config']['bonus']["problem_$problem_id"]);
		unset($contest['extra_config']['submit_time_limit']["problem_$problem_id"]);

		$esc_extra_config = json_encode($contest['extra_config']);
		DB::update([
			"update contests",
			"set", ["extra_config" => $esc_extra_config],
			"where", ["id" => $contest['id']]
		]);

		dieWithAlert('移除成功！');
	}

	$add_problem_form = new UOJBs4Form('add_problem');
	$add_problem_form->addVInput(
		'problem_id',
		'text',
		'题目 ID',
		'',
		function ($problem_id, &$vdata) {
			$problem = UOJProblem::query($problem_id);
			if (!$problem) {
				return '题目不存在。';
			}

			if (!$problem->userCanManage(Auth::user())) {
				return "无权添加此题目。";
			}

			if (UOJContest::cur()->hasProblem($problem)) {
				return "题目已经在本场比赛中。";
			}

			$vdata['problem_id'] = $problem_id;

			return '';
		},
		null
	);
	$add_problem_form->addVSelect('judge_config', [
		'default' => '默认',
		'sample' => '只测样例',
		'no-details' => '测试全部数据，对于每个测试点显示得分但不显示详情',
		'full' => '测试全部数据',
	], '评测设置', 'default');
	$add_problem_form->addVCheckboxes('bonus', ['0' => '否', '1' => '是'], '是否为 bonus 题（ACM 赛制）', '0');
	$add_problem_form->handle = function (&$vdata) use ($contest) {
		$level = DB::selectFirst([
			"select", "max(level)",
			"from", "contests_problems",
			"where", [
				"contest_id" => $contest['id'],
			]
		])["max(level)"];
		DB::insert([
			"insert ignore into contests_problems",
			"(contest_id, problem_id, level)",
			"values", DB::tuple([$contest['id'], $vdata['problem_id'], $level + 1])
		]);

		$judge_type = $_POST['judge_config'];
		if ($judge_type === 'default') {
			unset($contest['extra_config']["problem_{$vdata['problem_id']}"]);
		} else {
			$contest['extra_config']["problem_{$vdata['problem_id']}"] = $judge_type;
		}

		$esc_extra_config = json_encode($contest['extra_config']);
		DB::update([
			"update contests",
			"set", ["extra_config" => $esc_extra_config],
			"where", ["id" => $contest['id']]
		]);

		dieWithJsonData(['status' => 'success', 'message' => "题目 #{$vdata['problem_id']} 添加成功！"]);
	};
	$add_problem_form->submit_button_config['text'] = '添加';
	$add_problem_form->submit_button_config['class_str'] = 'btn btn-secondary mt-3';
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
		$user = UOJUser::query($username);

		if (!$user) {
			dieWithAlert('用户不存在。');
		}

		if (!UOJContest::cur()->userCanManage($user)) {
			dieWithAlert('用户不是这场比赛的管理员。');
		}


		DB::delete([
			"delete from contests_permissions",
			"where", [
				"contest_id" => $contest['id'],
				"username" => $user['username'],
			]
		]);
		dieWithAlert('移除成功！');
	}

	$add_manager_form = new UOJBs4Form('add_manager');
	$add_manager_form->addVInput(
		'username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) use ($contest) {
			$user = UOJUser::query($username);

			if (!$user) {
				return '用户不存在';
			}

			if (UOJContest::cur()->userCanManage($user)) {
				return '用户已经是这场比赛的管理员';
			}

			$vdata['username'] = $username;

			return '';
		},
		null
	);
	$add_manager_form->handle = function (&$vdata) use ($contest) {
		DB::insert([
			"insert into contests_permissions",
			"(contest_id, username)",
			"values", DB::tuple([$contest['id'], $vdata['username']])
		]);

		dieWithJsonData(['status' => 'success', 'message' => '已将用户名为 ' . $vdata['username'] . ' 的用户设置为本场比赛的管理者。']);
	};
	$add_manager_form->submit_button_config['text'] = '添加';
	$add_manager_form->submit_button_config['class_str'] = 'btn btn-secondary mt-3';
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
	$version_form = new UOJBs4Form('version');
	$version_form->addVSelect('standings_version', [
		'1' => '1',
		'2' => '2',
	], '比赛排名版本', $contest['extra_config']['standings_version']);
	$version_form->handle = function () use ($contest) {
		$contest['extra_config']['standings_version'] = $_POST['standings_version'];
		$esc_extra_config = json_encode($contest['extra_config']);
		DB::update([
			"update contests",
			"set", ["extra_config" => $esc_extra_config],
			"where", ["id" => $contest['id']],
		]);

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

	$rule_form = new UOJBs4Form('basic_rule');
	$rule_form->addVSelect('basic_rule', [
		'OI' => 'OI',
		'IOI' => 'IOI',
		'ACM' => 'ACM',
	], '比赛类型', $contest['extra_config']['basic_rule']);
	$rule_form->addVSelect('free_registration', [
		1 => '所有人都可以自由报名',
		0 => '只能由管理员帮选手报名'
	], "报名方式", $contest['extra_config']['free_registration']);
	$rule_form->addVSelect('individual_or_team', [
		'individual' => '个人赛',
		'team' => '团体赛'
	], "个人赛/团体赛", $contest['extra_config']['individual_or_team']);
	$rule_form->addVInput(
		'max_n_submissions_per_problem',
		'text',
		'每题最高提交次数（-1 表示不限制）',
		$contest['extra_config']['max_n_submissions_per_problem'],
		function ($str) {
			return !validateUInt($str) && $str !== '-1' ? '必须为一个非负整数或 -1' : '';
		},
		null
	);
	$rule_form->handle = function () use ($contest) {
		$contest['extra_config']['basic_rule'] = $_POST['basic_rule'];
		$contest['extra_config']['free_registration'] = (int)$_POST['free_registration'];
		$contest['extra_config']['individual_or_team'] = $_POST['individual_or_team'];
		$contest['extra_config']['max_n_submissions_per_problem'] = (int)$_POST['max_n_submissions_per_problem'];

		$esc_extra_config = json_encode($contest['extra_config']);
		DB::update([
			"update contests",
			"set", ["extra_config" => $esc_extra_config],
			"where", ["id" => $contest['id']]
		]);

		dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
	};
	$rule_form->setAjaxSubmit(<<<EOD
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
	$rule_form->runAtServer();

	$blog_link_contests = new UOJBs4Form('blog_link_contests');
	$blog_link_contests->addVInput(
		'blog_id',
		'text',
		'博客 ID',
		'',
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
	$blog_link_contests->addVInput(
		'title',
		'text',
		'名称',
		'',
		function ($title, &$vdata) {
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
	$blog_link_contests->handle = function ($vdata) use ($contest) {
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
}

?>
<?php echoUOJPageHeader(HTML::stripTags('比赛管理 - ' . $contest['name'])) ?>
<h1>
	<?= $contest['name'] ?>
	<small class="fs-5">(ID: <?= $contest['id'] ?>)</small>
	管理
</h1>

<div class="row mt-4">
	<!-- left col -->
	<div class="col-md-3">

		<?= HTML::navListGroup($tabs_info, $cur_tab) ?>

		<a class="btn btn-light d-block mt-2 w-100 text-start text-primary" style="--bs-btn-hover-bg: #d3d4d570; --bs-btn-hover-border-color: transparent;" href="<?= HTML::url("/contest/{$contest['id']}") ?>">
			<i class="bi bi-arrow-left"></i> 返回
		</a>

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
		<?php elseif ($cur_tab == 'problems') : ?>
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
							'ORDER BY level, problem_id',
							<<<EOD
	<tr>
		<th style="width:3em">ID</th>
		<th>标题</th>
		<th style="width:8em">评测设置</th>
		<th style="width:6em">操作</th>
	</tr>
EOD,
							function ($row) {
								$problem = UOJProblem::query($row['problem_id']);
								echo '<tr>';
								echo '<td>', $row['problem_id'], '</td>';
								echo '<td>', $problem->getLink(['with' => 'none']), '</td>';
								echo '<td>', isset($contest['extra_config']["problem_{$problem->info['id']}"]) ? $contest['extra_config']["problem_{$problem->info['id']}"] : 'default', '</td>';
								echo '<td>';
								echo '<form class="d-inline-block" method="POST" target="_self" onsubmit=\'return confirm("你确定要将题目 #', $problem->info['id'], ' 从比赛中移除吗？")\'>';
								echo '<input type="hidden" name="_token" value="', crsf_token(), '">';
								echo '<input type="hidden" name="problem_id" value="', $problem->info['id'], '">';
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
									<li>在 ACM 赛制中，如果设置一道题目为 bonus 题，那么获得 100 分后会总罚时会减少 20 分钟，但排名时不会将此题记入该选手通过的题目总数中。</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php elseif ($cur_tab == 'managers') : ?>
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
		<?php elseif ($cur_tab == 'others') : ?>
			<div class="card mt-3 mt-md-0">
				<div class="card-header">
					<ul class="nav nav-tabs card-header-tabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" href="#type" data-bs-toggle="tab" data-bs-target="#type">规则</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#standings-version" data-bs-toggle="tab" data-bs-target="#standings-version">排名版本</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#blogs" data-bs-toggle="tab" data-bs-target="#blogs">比赛资料</a>
						</li>
					</ul>
				</div>
				<div class="card-body tab-content">
					<div class="tab-pane active" id="type">
						<div id="type-result-alert" class="alert" role="alert" style="display: none"></div>
						<div class="row row-cols-1 row-cols-md-2">
							<div class="col">
								<?php $rule_form->printHTML(); ?>
							</div>
							<div class="col mt-3 mt-md-0">
								<h5>赛制解释</h5>
								<ul>
									<li><strong>OI 赛制：</strong> 比赛期间可设置题目只测样例，结束后会进行重测。按最后一次有效提交算分和算罚时。</li>
									<li><strong>ACM 赛制：</strong> 比赛期间所有题目显示最终测评结果。比赛结束前一小时封榜，比赛时间不足 5 小时则比赛过去五分之四后封榜。一道题的罚时为得分最高的提交的时间，加上在此之前没有使得该题分数增加的提交的次数乘以 20 分钟。</li>
									<li><strong>IOI 赛制：</strong> 比赛期间所有题目显示最终测评结果。按得分最高的有效提交算分和算罚时。</li>
								</ul>
								<h5>常见问题</h5>
								<ul class="mb-0">
									<li></li>
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
				</div>
			</div>
	</div>
<?php endif ?>
</div>
<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
