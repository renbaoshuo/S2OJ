<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();
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

$view_type_form = new UOJForm('view_type');
$view_type_form->addSelect('view_content_type', [
	'div_class' => 'row align-items-center g-0',
	'label_class' => 'form-label col-auto m-0 flex-grow-1',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看提交文件',
	'options' => [
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' => $problem_extra_config['view_content_type'],
]);
$view_type_form->addSelect('view_all_details_type', [
	'div_class' => 'row align-items-center g-0 mt-3',
	'label_class' => 'form-label col-auto m-0 flex-grow-1',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看全部详细信息',
	'options' => [
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人'
	],
	'default_value' => $problem_extra_config['view_all_details_type'],
]);
$view_type_form->addSelect('view_details_type', [
	'div_class' => 'row align-items-center g-0 mt-3',
	'label_class' => 'form-label col-auto m-0 flex-grow-1',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看测试点详细信息',
	'options' => [
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' => $problem_extra_config['view_details_type'],
]);
$view_type_form->handle = function () {
	$config = UOJProblem::cur()->getExtraConfig();
	$config['view_content_type'] = $_POST['view_content_type'];
	$config['view_all_details_type'] = $_POST['view_all_details_type'];
	$config['view_details_type'] = $_POST['view_details_type'];
	$esc_config = json_encode($config);

	DB::update([
		"update problems",
		"set", ["extra_config" => $esc_config],
		"where", ["id" => UOJProblem::info('id')]
	]);
};
$view_type_form->runAtServer();

$solution_view_type_form = new UOJForm('solution_view_type');
$solution_view_type_form->addSelect('view_solution_type', [
	'div_class' => 'row align-items-center g-0',
	'label_class' => 'form-label col-auto m-0 flex-grow-1',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看题解',
	'options' => [
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' => $problem_extra_config['view_solution_type'],
]);
$solution_view_type_form->addSelect('submit_solution_type', [
	'div_class' => 'row align-items-center g-0 mt-3',
	'label_class' => 'form-label col-auto m-0 flex-grow-1',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '提交题解',
	'options' => [
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' => $problem_extra_config['submit_solution_type'],
]);
$solution_view_type_form->handle = function () {
	$config = UOJProblem::cur()->getExtraConfig();
	$config['view_solution_type'] = $_POST['view_solution_type'];
	$config['submit_solution_type'] = $_POST['submit_solution_type'];
	$esc_config = json_encode($config);

	DB::update([
		"update problems",
		"set", ["extra_config" => $esc_config],
		"where", ["id" => UOJProblem::info('id')]
	]);
};
$solution_view_type_form->runAtServer();
?>

<?php echoUOJPageHeader('题面编辑 - ' . HTML::stripTags(UOJProblem::info('title'))) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>
			<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?> 管理
		</h1>

		<ul class="nav nav-pills my-3" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" href="/problem/<?= UOJProblem::info('id') ?>/manage/statement" role="tab">
					题面
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/manage/managers" role="tab">
					管理者
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/manage/data" role="tab">
					数据
				</a>
			</li>
		</ul>

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
					<li>请勿引用不稳定的外部资源（如来自个人服务器的图片或文档等），以便备份及后期维护；</li>
					<li>请勿在题面中直接插入大段 HTML 代码，这可能会破坏页面的显示，可以考虑使用 <a class="text-decoration-none" href="/html2markdown" target="_blank">转换工具</a> 转换后再作修正；</li>
					<li>图片上传推荐使用 <a class="text-decoration-none" href="/image_hosting" target="_blank">S2OJ 图床</a>，以免后续产生外链图片大量失效的情况。</li>
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

		<div class="card mt-3">
			<div class="card-header fw-bold">
				提交记录可视权限
			</div>
			<div class="card-body">
				<?php $view_type_form->printHTML() ?>
			</div>
		</div>

		<div class="card mt-3">
			<div class="card-header fw-bold">
				题解可视权限
			</div>
			<div class="card-body">
				<?php $solution_view_type_form->printHTML() ?>
			</div>
		</div>
	</aside>
</div>

<?php echoUOJPageFooter() ?>
