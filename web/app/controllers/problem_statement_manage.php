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
	'options' => [-1 => '暂无评定'] + array_combine(UOJProblem::$difficulty, UOJProblem::$difficulty),
	'default_value' => UOJProblem::info('difficulty'),
]);
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
			<div class="card-footer bg-transparent">
				评价：<?= UOJProblem::cur()->getZanBlock() ?>
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
	</aside>

</div>

<?php echoUOJPageFooter() ?>
