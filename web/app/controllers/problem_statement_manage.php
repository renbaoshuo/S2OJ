<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$tabs_info = [
	'statement' => [
		'name' => '题面',
		'url' => UOJProblem::cur()->getUri('/manage/statement'),
	],
	'permissions' => [
		'name' => '权限',
		'url' => UOJProblem::cur()->getUri('/manage/permissions'),
	],
];

if (UOJProblem::info('type') === 'local') {
	$tabs_info['data'] = [
		'name' => '数据',
		'url' => UOJProblem::cur()->getUri('/manage/data'),
	];
} else if (UOJProblem::info('type') === 'remote') {
	//
}

$problem_content = UOJProblem::cur()->queryContent();

$problem_editor = new UOJBlogEditor();
$problem_editor->name = 'problem';
$problem_editor->blog_url = '/problem/' . UOJProblem::info('id');
$problem_editor->cur_data = [
	'title' => UOJProblem::info('title'),
	'content_md' => $problem_content['statement_md'],
	'content' => $problem_content['statement'],
	'tags' => UOJProblem::cur()->queryTags(),
	'is_hidden' => UOJProblem::info('is_hidden')
];
$problem_editor->label_text = array_merge($problem_editor->label_text, [
	'view blog' => '查看题目',
	'blog visibility' => '题目可见性'
]);

$problem_editor->save = function ($data) {
	DB::update([
		"update problems",
		"set", ["title" => $data['title']],
		"where", ["id" => UOJProblem::info('id')]
	]);

	DB::update([
		"update problems_contents",
		"set", [
			"statement" => $data['content'],
			"statement_md" => $data['content_md']
		], "where", ["id" => UOJProblem::info('id')]
	]);

	UOJProblem::cur()->updateTags($data['tags']);

	if ($data['is_hidden'] != UOJProblem::info('is_hidden')) {
		DB::update([
			"update problems",
			"set", ["is_hidden" => $data['is_hidden']],
			"where", ["id" => UOJProblem::info('id')]
		]);

		DB::update([
			"update submissions",
			"set", ["is_hidden" => $data['is_hidden']],
			"where", ["problem_id" => UOJProblem::info('id')]
		]);

		DB::update([
			"update hacks",
			"set", ["is_hidden" => $data['is_hidden']],
			"where", ["problem_id" => UOJProblem::info('id')]
		]);
	}
};

$problem_editor->runAtServer();

$difficulty_form = new UOJForm('difficulty');
$difficulty_form->addSelect('difficulty', [
	'div_class' => 'flex-grow-1',
	'options' => [-1 => '暂无评定'] + array_combine(UOJProblem::$difficulty, UOJProblem::$difficulty),
	'default_value' => UOJProblem::info('difficulty'),
]);
$difficulty_form->config['form']['class'] = 'd-flex';
$difficulty_form->config['submit_container']['class'] = 'ms-2';
$difficulty_form->handle = function () {
	DB::update([
		"update problems",
		"set", [
			"difficulty" => $_POST['difficulty'],
		],
		"where", [
			"id" => UOJProblem::info('id'),
		],
	]);
};
$difficulty_form->runAtServer();

if (UOJProblem::info('type') == 'remote') {
	$remote_online_judge = UOJProblem::cur()->getExtraConfig('remote_online_judge');
	$remote_problem_id = UOJProblem::cur()->getExtraConfig('remote_problem_id');
	$remote_provider = UOJRemoteProblem::$providers[$remote_online_judge];

	$re_crawl_form = new UOJForm('re_crawl');
	$re_crawl_form->appendHTML(<<<EOD
		<ul>
			<li>远程题库：{$remote_provider['name']}</li>
			<li>远程题号：{$remote_problem_id}</li>
		</ul>
	EOD);
	$re_crawl_form->config['submit_button']['text'] = '重新爬取';
	$re_crawl_form->handle = function () use ($remote_online_judge, $remote_problem_id) {
		try {
			$data = UOJRemoteProblem::getProblemBasicInfo($remote_online_judge, $remote_problem_id);
		} catch (Exception $e) {
			$data = null;
			UOJLog::error($e->getMessage());
		}

		if ($data === null) {
			UOJResponse::page500('题目抓取失败，可能是题目不存在或者没有题面！如果题目没有问题，请稍后再试。<a href="">返回</a>');
		}

		if ($data['difficulty'] == -1) {
			$data['difficulty'] = UOJProblem::info('difficulty');
		}

		$submission_requirement = UOJRemoteProblem::getSubmissionRequirements($remote_online_judge);
		$enc_submission_requirement = json_encode($submission_requirement);
	
		$extra_config = [
			'remote_online_judge' => $remote_online_judge,
			'remote_problem_id' => $remote_problem_id,
			'time_limit' => $data['time_limit'],
			'memory_limit' => $data['memory_limit'],
		];
		$enc_extra_config = json_encode($extra_config);

		DB::update([
			"update problems",
			"set", [
				"title" => $data['title'],
				"submission_requirement" => $enc_submission_requirement,
				"extra_config" => $enc_extra_config,
				"difficulty" => $data['difficulty'] ?: -1,
			],
			"where", [
				"id" => UOJProblem::info('id'),
			],
		]);

		if ($data['type'] == 'pdf') {
			file_put_contents(UOJContext::storagePath() . "/problem_resources/" . UOJProblem::info('id') . "/statement.pdf", $data['pdf_data']);
			$data['statement'] = '<div data-pdf data-src="/problem/' . UOJProblem::info('id') . '/resources/statement.pdf"></div>' . "\n" . $data['statement'];
		}

		DB::update([
			"update problems_contents",
			"set", [
				"remote_content" => HTML::purifier(['a' => ['target' => 'Enum#_blank']])->purify($data['statement']),
			],
			"where", [
				"id" => UOJProblem::info('id'),
			],
		]);

		UOJRemoteProblem::downloadImagesInRemoteContent(UOJProblem::info('id'));

		redirectTo(UOJProblem::cur()->getUri());
	};
	$re_crawl_form->runAtServer();

	$convert_local_form = new UOJForm('convert_local');
	$convert_local_form->handle = function () {
		DB::update([
			"update problems",
			"set", [
				"type" => 'local',
				"submission_requirement" => "{}",
				"extra_config" => "{}",
			],
			"where", [
				"id" => UOJProblem::info('id'),
			],
		]);

		DB::update([
			"update problems_contents",
			"set", [
				"remote_content" => '',
			],
			"where", [
				"id" => UOJProblem::info('id'),
			],
		]);
	};
	$convert_local_form->config['submit_container']['class'] = '';
	$convert_local_form->config['submit_button']['class'] = 'btn btn-danger';
	$convert_local_form->config['submit_button']['text'] = '将本题转换为本地题目（不可逆）';
	$convert_local_form->config['confirm']['text'] = '您真的要*不可逆*地将本题转换为本地题目吗？';
	$convert_local_form->runAtServer();
}
?>

<?php echoUOJPageHeader('题面管理 - ' . HTML::stripTags(UOJProblem::info('title'))) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>
			<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?> 管理
		</h1>

		<div class="my-3">
			<?= HTML::tablist($tabs_info, 'statement', 'nav-pills') ?>
		</div>

		<div class="card card-default">
			<div class="card-body">
				<?php $problem_editor->printHTML() ?>
			</div>
		</div>

		<!-- 提示信息 -->
		<div class="card mt-3">
			<div class="card-body">
				<h2 class="h3 card-title">提示</h2>
				<ol>
					<li>请勿引用不稳定的外部资源（如来自个人服务器的图片或文档等），可以上传至 <a href="<?= UOJProblem::cur()->getUri('/resources') ?>">题目资源</a> 中，以便备份及后期维护；</li>
					<li>请勿在题面中直接插入大段 HTML 代码，这可能会破坏页面的显示，可以考虑使用 <a class="text-decoration-none" href="/apps/html2markdown" target="_blank">转换工具</a> 转换后再作修正；</li>
					<li>图片上传推荐使用 <a class="text-decoration-none" href="/apps/image_hosting" target="_blank">S2OJ 图床</a>，以免后续产生外链图片大量失效的情况。</li>
				</ol>
				<p class="card-text">
					更多内容请查看 S2OJ 用户手册中的「<a class="text-decoration-none" href="https://s2oj.github.io/#/manage/problem?id=%e4%bc%a0%e9%a2%98%e6%8c%87%e5%bc%95">传题指引</a>」部分。
				</p>
			</div>
		</div>
	</div>

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<div class="card card-default mb-2">
			<ul class="nav nav-pills nav-fill flex-column" role="tablist">
				<li class="nav-item text-start">
					<a href="/problem/<?= UOJProblem::info('id') ?>" class="nav-link" role="tab">
						<i class="bi bi-journal-text"></i>
						<?= UOJLocale::get('problems::statement') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a href="/problem/<?= UOJProblem::info('id') ?>#submit" class="nav-link" role="tab">
						<i class="bi bi-upload"></i>
						<?= UOJLocale::get('problems::submit') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a href="/problem/<?= UOJProblem::info('id') ?>/solutions" class="nav-link" role="tab">
						<i class="bi bi-journal-bookmark"></i>
						<?= UOJLocale::get('problems::solutions') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a class="nav-link" href="/submissions?problem_id=<?= UOJProblem::info('id') ?>">
						<i class="bi bi-list-ul"></i>
						<?= UOJLocale::get('submissions') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/statistics">
						<i class="bi bi-graph-up"></i>
						<?= UOJLocale::get('problems::statistics') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a class="nav-link active" href="#" role="tab">
						<i class="bi bi-sliders"></i>
						<?= UOJLocale::get('problems::manage') ?>
					</a>
				</li>
			</ul>
		</div>

		<div class="card mt-3">
			<div class="card-header fw-bold">
				标签填充
			</div>
			<div class="card-body">
				<script>
					function fillTag(tags) {
						if (typeof tags === 'string') tags = [tags];

						tags = tags.map(tag => tag.trim()).filter(Boolean);

						var originalTags = $('#input-problem_tags')
							.val()
							.replace(/，/g, ',')
							.split(',')
							.map(tag => tag.trim())
							.filter(Boolean);
						var newTagsSet = new Set(originalTags.concat(tags));

						$('#input-problem_tags').val(Array.from(newTagsSet.values()).join(', '));
						$('#input-problem_tags').trigger('input');
					}
				</script>

				<div class="row row-cols-4 row-cols-lg-2 g-2">
					<?php foreach (UOJProblem::$categories as $category => $tags) : ?>
						<?php $category_id = uniqid('category-'); ?>

						<div class="d-inline-block" id="category-container-<?= $category_id ?>">
							<button id="category-button-<?= $category_id ?>" class="btn btn-sm btn-light w-100" type="button"><?= $category ?></button>
						</div>

						<script>
							$(document).ready(function() {
								bootstrap.Popover.jQueryInterface.call($('#category-button-<?= $category_id ?>'), {
									container: $('#category-container-<?= $category_id ?>'),
									html: true,
									placement: 'left',
									animation: false,
									trigger: 'manual',
									fallbackPlacements: ['bottom', 'right'],
									content: [
										<?php foreach ($tags as $tag) : ?> '<?= $tag ?>', <?php endforeach ?>
									].map(tag => ('<button class="btn btn-sm btn-light d-inline-block mr-1 mb-1" onclick="fillTag([\'<?= $category ?>\', \'' + tag + '\'])">' + tag + '</button>')).join(' '),
									sanitizeFn(content) {
										return content;
									},
								}).on("mouseenter", function() {
									var _this = this;

									$(this).popover("show");
									$(this).siblings(".popover").on("mouseleave", function() {
										$(_this).popover('hide');
									});
								}).on("mouseleave", function() {
									var _this = this;

									var check_popover_status = function() {
										setTimeout(function() {
											if (!$(".popover:hover").length) {
												$(_this).popover("hide")
											} else {
												check_popover_status();
											}
										}, 50);
									};

									check_popover_status();
								});
							});
						</script>
					<?php endforeach ?>
				</div>
			</div>
			<div class="card-footer text-muted small bg-transparent">
				将鼠标悬浮至主分类上，点击弹出框中的对应标签即可将其填充至题目标签中。
			</div>
		</div>

		<div class="card mt-3">
			<div class="card-header fw-bold">
				题目难度
			</div>
			<div class="card-body">
				<?php $difficulty_form->printHTML() ?>
			</div>
		</div>

		<?php if (UOJProblem::info('type') == 'remote') : ?>
			<div class="card mt-3">
				<div class="card-header fw-bold">
					重新爬取题目信息
				</div>
				<div class="card-body">
					<?php $re_crawl_form->printHTML() ?>
				</div>
			</div>

			<div class="card mt-3 border-danger">
				<div class="card-header fw-bold text-bg-danger border-danger">
					转换为本地题目
				</div>
				<div class="card-body border-danger">
					<?php $convert_local_form->printHTML() ?>
				</div>
				<div class="card-footer bg-transparent small text-muted border-danger">
					转换为本地题目之后可以上传自行准备的测试数据进行评测。转换后不再将代码提交至远端 OJ 进行评测。该操作不可逆。
				</div>
			</div>
		<?php endif ?>
	</aside>
</div>

<?php echoUOJPageFooter() ?>
