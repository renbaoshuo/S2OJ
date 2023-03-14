<?php
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
		'div_class' => 'mb-3',
		'label' => '标题',
		'default_value' => HTML::unescape(UOJList::info('title')),
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
		'div_class' => 'mb-3',
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
		'div_class' => 'mb-3',
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
	$update_profile_form->addMarkdownEditor('content_md', [
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
	$problems_form = newAddDelCmdForm(
		'problems',
		function ($problem_id) {
			if (!validateUInt($problem_id)) {
				return "无效题号";
			}

			$problem = UOJProblem::query($problem_id);

			if (!$problem) {
				return "不存在题号为 {$problem_id} 的题";
			}

			// if (!$problem->userCanManage(Auth::user())) {
			// 	return "无权添加题号为 {$problem_id} 的题";
			// }

			if (!$problem->userCanView(Auth::user())) {
				return "无权添加题号为 {$problem_id} 的题";
			}

			return '';
		},
		function ($type, $problem_id) {
			if ($type == '+') {
				DB::insert([
					"insert into lists_problems",
					DB::bracketed_fields(["list_id", "problem_id"]),
					"values", DB::tuple([UOJList::info('id'), $problem_id]),
				]);
			} else if ($type == '-') {
				DB::delete([
					"delete from lists_problems",
					"where", [
						"list_id" => UOJList::info('id'),
						"problem_id" => $problem_id,
					],
				]);
			}
		},
		null,
		[
			'help' => '命令格式：命令一行一个，<code>+233</code> 表示把题号为 <code>233</code> 的试题加入题单，<code>-233</code> 表示把题号为 <code>233</code> 的试题从题单中移除。',
		]
	);
	$problems_form->runAtServer();

	$n_problems = DB::selectCount([
		"select count(*)",
		"from lists_problems",
		"where", [
			"list_id" => UOJList::info('id'),
		],
	]);
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
					<div class="row">
						<div class="col-md-8">
							<?= $update_profile_form->printHTML() ?>
						</div>
						<div class="col-md-4">
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
				<div class="card-body">
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
							echo HTML::tag_end('tr');
						},
						[
							'echo_full' => true,
							'div_classes' => ['table-responsive'],
							'table_classes' => ['table', 'align-middle'],
							'print_after_table' => function () use ($n_problems) {
								echo '<div class="text-muted text-end">共 ', $n_problems, ' 道题目</div>';
							},
						]
					);
					?>

					<?php $problems_form->printHTML() ?>
				</div>
			</div>
		<?php endif ?>
	</div>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
