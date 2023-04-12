<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanManage(Auth::user()) || UOJResponse::page403();
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
	$profile_form = new UOJForm('time');
	$profile_form->addInput('name',	[
		'label' => '比赛标题',
		'default_value' => HTML::unescape(UOJContest::info('name')),
		'validator_php' => function ($name, &$vdata) {
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
	]);
	$profile_form->addInput('start_time', [
		'div_class' => 'mt-3',
		'label' => '开始时间',
		'default_value' => UOJContest::info('start_time_str'),
		'validator_php' => function ($start_time, &$vdata) {
			try {
				$vdata['start_time'] = new DateTime($start_time);
			} catch (Exception $e) {
				return '无效时间格式';
			}
			return '';
		},
	]);
	$profile_form->addInput('last_min', [
		'div_class' => 'mt-3',
		'label' => '时长',
		'help' => '单位为分钟。',
		'default_value' => UOJContest::info('last_min'),
		'validator_php' => function ($last_min, &$vdata) {
			if (!validateUInt($last_min)) {
				return '必须为一个整数';
			}
			$vdata['last_min'] = $last_min;
			return '';
		},
	]);
	$profile_form->handle = function (&$vdata) {
		DB::update([
			"update contests",
			"set", [
				"name" => $vdata['name'],
				"start_time" => UOJTime::time2str($vdata['start_time']),
				"last_min" => $vdata['last_min'],
			], "where", ["id" => UOJContest::info('id')]
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

	if (UOJContest::cur()->getProblemsCount() >= 26) {
		$add_problem_form_msg = '比赛中的题目数量已达上限。';
	} else if (UOJContest::cur()->progress() > CONTEST_IN_PROGRESS) {
		$add_problem_form_msg = '比赛已结束。';
	} else {
		$add_problem_form = new UOJForm('add_problem');
		$add_problem_form->addInput('problem_id', [
			'label' => '题目 ID',
			'validator_php' => function ($problem_id, &$vdata) {
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
		]);
		$add_problem_form->addSelect('judge_config', [
			'div_class' => 'mt-3',
			'label' => '评测设置',
			'options' => [
				'default' => '默认',
				'sample' => '只测样例',
				'no-details' => '测试全部数据，对于每个测试点显示得分但不显示详情',
				'full' => '测试全部数据',
			],
			'default_value' => 'default',
		]);
		$add_problem_form->addCheckbox('bonus', [
			'div_class' => 'form-check mt-3',
			'label' => '是否为 bonus 题（针对 ACM 赛制）',
		]);
		$add_problem_form->handle = function (&$vdata) use ($contest) {
			$level = DB::selectSingle([
				"select", "max(level)",
				"from", "contests_problems",
				"where", [
					"contest_id" => UOJContest::info('id'),
				]
			]);
			DB::insert([
				"insert ignore into contests_problems",
				"(contest_id, problem_id, level)",
				"values", DB::tuple([UOJContest::info('id'), $vdata['problem_id'], $level + 1])
			]);

			$judge_type = $_POST['judge_config'];
			if ($judge_type === 'default') {
				unset($contest['extra_config']["problem_{$vdata['problem_id']}"]);
			} else {
				$contest['extra_config']["problem_{$vdata['problem_id']}"] = $judge_type;
			}

			if ($_POST['bonus']) {
				$contest['extra_config']['bonus']["problem_{$vdata['problem_id']}"] = true;
			} else {
				unset($contest['extra_config']['bonus']["problem_{$vdata['problem_id']}"]);
			}

			$esc_extra_config = json_encode($contest['extra_config']);
			DB::update([
				"update contests",
				"set", ["extra_config" => $esc_extra_config],
				"where", ["id" => UOJContest::info('id')]
			]);
		};
		$add_problem_form->config['submit_button']['text'] = '添加';
		$add_problem_form->config['submit_button']['class'] = 'btn btn-secondary mt-3';
		$add_problem_form->runAtServer();
	}
} elseif ($cur_tab == 'managers') {
	$managers_form = newAddDelCmdForm(
		'managers',
		'validateUserAndStoreByUsername',
		function ($type, $username, &$vdata) {
			$user = $vdata['user'][$username];

			if ($type == '+') {
				DB::insert([
					"insert into contests_permissions",
					"(contest_id, username)",
					"values", DB::tuple([
						UOJContest::info('id'), $user['username']
					])
				]);
			} else if ($type == '-') {
				DB::delete([
					"delete from contests_permissions",
					"where", [
						"contest_id" => UOJContest::info('id'),
						"username" => $user['username']
					]
				]);
			}
		},
		null,
		[
			'help' => '命令格式：命令一行一个，<code>+mike</code> 表示把 <code>mike</code> 加入管理者，<code>-mike</code> 表示把 <code>mike</code> 从管理者中移除。',
		]
	);
	$managers_form->runAtServer();
} elseif ($cur_tab == 'others') {
	$rule_form = new UOJForm('basic_rule');
	$rule_form->addSelect('basic_rule', [
		'label' => '比赛类型',
		'options' => [
			'OI' => 'OI',
			'IOI' => 'IOI',
			'ACM' => 'ACM',
		],
		'default_value' => $contest['extra_config']['basic_rule'],
	]);
	$rule_form->addSelect('free_registration', [
		'div_class' => 'mt-3',
		'label' => '报名方式',
		'options' => [
			1 => '所有人都可以自由报名',
			0 => '只能由管理员帮选手报名'
		],
		'default_value' => $contest['extra_config']['free_registration'],
	]);
	$rule_form->addCheckbox('extra_registration', [
		'div_class' => 'form-check mt-3',
		'label' => '允许额外报名',
		'checked' => $contest['extra_config']['extra_registration'] ?: true,
	]);
	$rule_form->addSelect('individual_or_team', [
		'div_class' => 'mt-3',
		'label' => '个人赛/团体赛',
		'options' => [
			'individual' => '个人赛',
			'team' => '团体赛',
		],
		'default_value' => $contest['extra_config']['individual_or_team'],
	]);
	$rule_form->addInput('max_n_submissions_per_problem', [
		'div_class' => 'mt-3',
		'label' => '每题最高提交次数',
		'type' => 'number',
		'default_value' => $contest['extra_config']['max_n_submissions_per_problem'],
		'help' => '设为 -1 表示无限制（系统默认频率限制仍有效）。',
		'validator_php' => function ($str) {
			return !validateUInt($str) && $str !== '-1' ? '必须为一个非负整数或 -1' : '';
		},
	]);
	$rule_form->addSelect('standings_version', [
		'div_class' => 'mt-3',
		'label' => '比赛排名版本',
		'options' => [1 => 1, 2 => 2],
		'default_value' => $contest['extra_config']['standings_version'],
	]);
	$rule_form->handle = function () use ($contest) {
		$contest['extra_config']['basic_rule'] = $_POST['basic_rule'];
		$contest['extra_config']['free_registration'] = (int)$_POST['free_registration'];
		$contest['extra_config']['individual_or_team'] = $_POST['individual_or_team'];
		$contest['extra_config']['max_n_submissions_per_problem'] = (int)$_POST['max_n_submissions_per_problem'];
		$contest['extra_config']['extra_registration'] = (int)$_POST['extra_registration'];
		$contest['extra_config']['standings_version'] = (int)$_POST['standings_version'];

		$esc_extra_config = json_encode($contest['extra_config']);
		DB::update([
			"update contests",
			"set", ["extra_config" => $esc_extra_config],
			"where", ["id" => UOJContest::info('id')]
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

			setTimeout(function() {
				$('#type-result-alert').hide();
			}, 5000);

			$(window).scrollTop(0);
		}
	EOD);
	$rule_form->runAtServer();

	$links = UOJContest::cur()->getAdditionalLinks();
	$links_str = json_encode($links, JSON_FORCE_OBJECT);
	$links_form = new UOJForm('links');
	$links_form->add('contest_links', '', function ($str, &$vdata) {
		$data = json_decode($str, true);
		$new_data = [];

		if ($data === null) return '不合法的 JSON';

		foreach ($data as $idx => $link) {
			$link_name = trim($link['name']);
			$link_url = trim($link['url']);

			if ($link_name && $link_url) {
				$new_data[] = [
					'name' => $link_name,
					'url' => $link_url,
				];
			}
		}

		$vdata['links'] = $new_data;

		return '';
	}, null);
	$links_form->appendHTML(<<<EOD
		<div id="div-contest_links"></div>
		<input type="hidden" name="contest_links" id="input-contest_links" value="">
		<script>
			var contest_links = {$links_str};
			var contest_links_cnt = Object.keys(contest_links).length;

			$(document).ready(function() {
				$('#input-contest_links').val(JSON.stringify(contest_links));

				function newLinkRow(idx) {
					var div_link = $('<div class="row mt-2" />');
					var input_link_name = $('<input type="text" class="form-control" placeholder="名称" />').val(contest_links[idx].name);
					var input_link_url = $('<input type="text" class="form-control" placeholder="链接" />').val(contest_links[idx].url);
					var btn_del_cur_link = $('<button type="button" class="btn btn-sm btn-outline-secondary" />').html('<i class="bi bi-x-lg"></i>');

					input_link_name.change(function() {
						contest_links[idx].name = input_link_name.val();
						$('#input-contest_links').val(JSON.stringify(contest_links));
					});
					input_link_url.change(function() {
						contest_links[idx].url = input_link_url.val();
						$('#input-contest_links').val(JSON.stringify(contest_links));
					});
					btn_del_cur_link.click(function() {
						contest_links[idx] = undefined;
						$('#input-contest_links').val(JSON.stringify(contest_links));
						div_link.remove();
					});

					div_link.append(
						$('<div class="col-11" />').append(
							$('<div class="row" />').append(
								$('<div class="col-md-6" />').append(input_link_name)
							).append(
								$('<div class="col-md-6" />').append(input_link_url)
							)
						)
					).append(
						$('<div class="col-1 text-center" />').append(btn_del_cur_link)
					);

					return div_link;
				};

				$.map(contest_links, function(link, idx) {
					$('#div-contest_links').append(newLinkRow(idx));
				});

				var row_add_link = $('<div class="row mt-2 justify-content-end" />');
				var btn_add_link = $('<button type="button" class="btn btn-sm btn-outline-secondary" />').html('<i class="bi bi-plus-lg"></i>');
				btn_add_link.click(function() {
					contest_links[++contest_links_cnt] = {name:'', url:''};
					row_add_link.before(newLinkRow(contest_links_cnt));
				});

				$('#div-contest_links').append(row_add_link.append($('<div class="col-1 text-center" />').append(btn_add_link)));
			});
		</script>
	EOD);
	$links_form->setAjaxSubmit(<<<EOD
		function(res) {
			if (res.status === 'success') {
				$('#links-result-alert')
					.html('修改成功！')
					.addClass('alert-success')
					.removeClass('alert-danger')
					.show();
			} else {
				$('#links-result-alert')
					.html('修改失败。' + (res.message || ''))
					.removeClass('alert-success')
					.addClass('alert-danger')
					.show();
			}

			setTimeout(function() {
				$('#links-result-alert').hide();
			}, 5000);

			$(window).scrollTop(0);
		}
	EOD);
	$links_form->handle = function (&$vdata) {
		$extra_config = UOJContest::info('extra_config');
		$extra_config['links'] = $vdata['links'];
		$esc_extra_config = json_encode($extra_config);

		DB::update([
			"update contests",
			"set", [
				"extra_config" => $esc_extra_config,
			],
			"where", [
				"id" => UOJContest::info('id'),
			],
		]);

		dieWithJsonData(['status' => 'success', 'message' => '修改成功']);
	};
	$links_form->runAtServer();
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
								<li>请为选手预留合理的做题时间。一般而言，CSP 和 NOIP 的比赛时长为 4 小时，NOI 的比赛时长为 5 小时。</li>
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
								$problem = UOJContestProblem::query($row['problem_id'], UOJContest::cur());
								echo '<tr>';
								echo '<td>', $row['problem_id'], '</td>';
								echo '<td>', $problem->getLink(['with' => 'none']), '</td>';
								echo '<td>', $problem->getJudgeTypeInContest(), '</td>';
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
								<?php if (isset($add_problem_form)) : ?>
									<?php $add_problem_form->printHTML() ?>
								<?php else : ?>
									<div class="alert alert-warning d-flex align-items-center my-0" role="alert">
										<div class="flex-shrink-0 me-3">
											<i class="fs-4 bi bi-exclamation-triangle-fill"></i>
										</div>
										<div>
											<div class="fw-bold mb-2">当前比赛无法添加新题目</div>
											<?php if (isset($add_problem_form_msg)) : ?>
												<div class="small"><?= $add_problem_form_msg ?></div>
											<?php endif ?>
										</div>
									</div>
								<?php endif ?>
							</div>
							<div class="col">
								<h5>注意事项</h5>
								<ul class="mt-0">
									<li>推荐在比赛结束前将题目设置为隐藏。</li>
									<li>对于「评测设置」选项，一般情况下保持默认（即只测样例）即可。</li>
									<li>在 ACM 赛制中，如果设置一道题目为 bonus 题，那么获得 100 分后会总罚时会减少 20 分钟，但排名时不会将此题记入该选手通过的题目总数中。</li>
									<li>一场比赛中最多添加 26 道题目。</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php elseif ($cur_tab == 'managers') : ?>
			<div class="card mt-3 mt-md-0">
				<div class="card-body">
					<?php
					echoLongTable(
						['*'],
						'contests_permissions',
						"contest_id = {$contest['id']}",
						'ORDER BY username',
						<<<EOD
							<tr>
								<th>用户名</th>
							</tr>
						EOD,
						function ($row) {
							$user = UOJUser::query($row['username']);

							echo HTML::tag_begin('tr');
							echo HTML::tag('td', [], UOJUser::getLink($user));
							echo HTML::tag_end('tr');
						},
						[
							'echo_full' => true,
							'div_classes' => ['table-responsive'],
							'table_classes' => ['table'],
						]
					);
					?>

					<?php $managers_form->printHTML() ?>
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
							<a class="nav-link" href="#contest-links" data-bs-toggle="tab" data-bs-target="#contest-links">链接</a>
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
									<li>团体赛推荐使用团体账号报名参赛以解锁全部功能。</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="tab-pane" id="contest-links">
						<div id="links-result-alert" class="alert" role="alert" style="display: none"></div>
						<?php $links_form->printHTML() ?>
					</div>
				</div>
			</div>
		<?php endif ?>
	</div>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
