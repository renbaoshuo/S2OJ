<?php

requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJList::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJList::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$cur_tab = UOJRequest::get('tab', 'is_string', 'profile');

$tabs_info = [
	'profile' => [
		'name' => '基本信息',
		'url' => '/list/' . UOJList::info('id') . '/manage/profile',
	],
	'problems' => [
		'name' => '题目管理',
		'url' => '/list/' . UOJList::info('id') . '/manage/problems',
	],
];

if (!isset($tabs_info[$cur_tab])) {
	become404Page();
}

if ($cur_tab == 'profile') {
	$update_profile_form = new UOJForm('update_profile');
	$update_profile_form->addInput('name', [
		'label' => '标题',
		'default_value' => UOJList::info('title'),
		'validator_php' => function ($title, &$vdata) {
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
	]);
	$update_profile_form->addCheckboxes('is_hidden', [
		'div_class' => 'mt-3',
		'label' => '可见性',
		'label_class' => 'me-3',
		'select_class' => 'd-inline-block',
		'option_div_class' => 'form-check d-inline-block ms-2',
		'default_value' => UOJList::info('is_hidden'),
		'options' => [
			0 => '公开',
			1 => '隐藏',
		],
	]);
	$update_profile_form->addInput('tags', [
		'label' => '标签',
		'default_value' => implode(', ', UOJList::cur()->queryTags()),
		'validator_php' => function ($tags_str, &$vdata) {
			$tags_raw = explode(',', str_replace('，', ',', $tags_str));
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
					return '标签 “' . $tag . '” 太长';
				}

				if (in_array($tag, $tags, true)) {
					return '标签 “' . $tag . '” 重复出现';
				}

				$tags[] = $tag;
			}

			$vdata['tags'] = $tags;

			return '';
		},
		'help' => '多个标签请使用逗号隔开。'
	]);
	$update_profile_form->addTextArea('content_md', [
		'label' => '描述',
		'default_value' => UOJList::cur()->queryContent()['content_md'],
		'validator_php' => function ($content_md, &$vdata) {
			if (strlen($content_md) > 5000) {
				return '描述过长';
			}

			$vdata['content_md'] = $content_md;

			return '';
		},
	]);
	$update_profile_form->handle = function ($vdata) {
		DB::update([
			"update lists",
			"set", [
				"title" => $vdata['title'],
				"is_hidden" => $_POST['is_hidden'],
			],
			"where", [
				"id" => UOJList::info('id'),
			]
		]);

		DB::update([
			"update lists_contents",
			"set", [
				"content" => HTML::purifier()->purify(HTML::parsedown()->text($vdata['content_md'])),
				"content_md" => $vdata['content_md'],
			],
			"where", [
				"id" => UOJList::info('id'),
			],
		]);

		UOJList::cur()->updateTags($vdata['tags']);

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
	$update_profile_form->config['submit_button']['text'] = '更新';
	$update_profile_form->runAtServer();
} elseif ($cur_tab == 'problems') {
	if (isset($_POST['submit-remove_problem']) && $_POST['submit-remove_problem'] == 'remove_problem') {
		crsf_defend();

		$problem = UOJProblem::query(UOJRequest::post('problem_id', 'validateUInt'));

		if (!$problem) {
			dieWithAlert('题目不存在');
		}

		if (!UOJList::cur()->hasProblem($problem)) {
			dieWithAlert('题目不在题单中');
		}

		DB::delete([
			"delete from lists_problems",
			"where", [
				"problem_id" => $problem->info['id'],
				"list_id" => UOJList::info('id'),
			],
		]);

		dieWithAlert('移除成功！');
	}

	$n_problems = DB::selectCount([
		"select count(*)",
		"from lists_problems",
		"where", [
			"list_id" => UOJList::info('id'),
		],
	]);

	$add_new_problem_form = new UOJForm('add_new_problem');
	$add_new_problem_form->addInput('problem_id', [
		'label' => '题目 ID',
		'validator_php' => function ($problem_id, &$vdata) {
			$problem = UOJProblem::query($problem_id);

			if (!$problem || !$problem->userCanView(Auth::user())) {
				return '题目不存在';
			}

			if (UOJList::cur()->hasProblem($problem)) {
				return '该题目已经在题单中';
			}

			$vdata['problem'] = $problem;

			return '';
		},
	]);
	$add_new_problem_form->config['submit_button']['text'] = '添加';
	$add_new_problem_form->handle = function ($vdata) {
		DB::insert([
			"insert into lists_problems",
			DB::bracketed_fields(["list_id", "problem_id"]),
			"values", DB::tuple([UOJList::info('id'), $vdata['problem']->info['id']]),
		]);

		dieWithJsonData(['status' => 'success', 'message' => '已将题目 #' . $vdata['problem']->info['id'] . ' 添加到题单 #' . UOJList::info('id') . ' 中']);
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
}
?>

<?php echoUOJPageHeader('管理 - ' . UOJList::info('title')) ?>

<h1>
	<?= UOJList::info('title') ?>
	<small class="fs-5">(ID: #<?= UOJList::info('id') ?>)</small>
	管理
</h1>

<div class="row mt-4">
	<!-- left col -->
	<div class="col-md-3">
		<?= HTML::navListGroup($tabs_info, $cur_tab) ?>

		<a class="btn btn-light d-block mt-2 w-100 text-start text-primary" style="--bs-btn-hover-bg: #d3d4d570; --bs-btn-hover-border-color: transparent;" href="<?= HTML::url('/list/' . UOJList::info('id')) ?>">
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
							['problem_id'],
							"lists_problems",
							["list_id" => UOJList::info('id')],
							"order by problem_id asc",
							<<<EOD
								<tr>
									<th class="text-center" style="width:5em">ID</th>
									<th>标题</th>
									<th style="width:4em">操作</th>
								</tr>
							EOD,
							function ($row) {
								$problem = UOJProblem::query($row['problem_id']);

								echo HTML::tag_begin('tr');
								echo HTML::tag('td', ['class' => 'text-center'], $problem->info['id']);
								echo HTML::tag_begin('td');
								echo $problem->getLink(['with' => 'none']);
								if ($problem->info['is_hidden']) {
									echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
								}
								echo HTML::tag_end('td');
								echo HTML::tag('td', [], HTML::tag('form', [
									'target' => '_self',
									'method' => 'POST',
									'class' => 'd-inline-block',
									'onsubmit' => "return confirm('你确定要将 {$problem->info['id']} 从题单中移除吗？');",
								], [
									HTML::hiddenToken(),
									HTML::empty_tag('input', ['type' => 'hidden', 'name' => 'problem_id', 'value' => $problem->info['id']]),
									html::tag('button', [
										'type' => 'submit',
										'class' => 'btn btn-link text-danger text-decoration-none p-0',
										'name' => 'submit-remove_problem',
										'value' => 'remove_problem',
									], '移除'),
								]));
								echo HTML::tag_end('tr');
							},
							[
								'page_len' => 10,
								'div_classes' => ['table-responsive'],
								'table_classes' => ['table', 'align-middle'],
								'print_after_table' => function () use ($n_problems) {
									echo '<div class="text-muted text-end">共 ', $n_problems, ' 道题目</div>';
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
		<?php endif ?>
	</div>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
