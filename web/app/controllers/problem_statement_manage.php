<?php
	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}
	
	$problem_content = queryProblemContent($problem['id']);
	$problem_tags = queryProblemTags($problem['id']);
	
	$problem_editor = new UOJBlogEditor();
	$problem_editor->name = 'problem';
	$problem_editor->blog_url = "/problem/{$problem['id']}";
	$problem_editor->cur_data = array(
		'title' => $problem['title'],
		'content_md' => $problem_content['statement_md'],
		'content' => $problem_content['statement'],
		'tags' => $problem_tags,
		'is_hidden' => $problem['is_hidden']
	);
	$problem_editor->label_text = array_merge($problem_editor->label_text, array(
		'view blog' => '查看题目',
		'blog visibility' => '题目可见性'
	));
	
	$problem_editor->save = function($data) {
		global $problem, $problem_tags;
		DB::update("update problems set title = '".DB::escape($data['title'])."' where id = {$problem['id']}");
		DB::update("update problems_contents set statement = '".DB::escape($data['content'])."', statement_md = '".DB::escape($data['content_md'])."' where id = {$problem['id']}");
		
		if ($data['tags'] !== $problem_tags) {
			DB::delete("delete from problems_tags where problem_id = {$problem['id']}");
			foreach ($data['tags'] as $tag) {
				DB::insert("insert into problems_tags (problem_id, tag) values ({$problem['id']}, '".DB::escape($tag)."')");
			}
		}
		if ($data['is_hidden'] != $problem['is_hidden'] ) {
			DB::update("update problems set is_hidden = {$data['is_hidden']} where id = {$problem['id']}");
			DB::update("update submissions set is_hidden = {$data['is_hidden']} where problem_id = {$problem['id']}");
			DB::update("update hacks set is_hidden = {$data['is_hidden']} where problem_id = {$problem['id']}");
		}
	};
	
	$problem_editor->runAtServer();
	?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 编辑 - 题目管理') ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">

<h1 class="h2">
	#<?=$problem['id']?>. <?=$problem['title']?> 管理
</h1>

<ul class="nav nav-pills my-3" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
			题面
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">
			管理者
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">
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
	<h2 class="h4 card-title">提示</h2>
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
			<a href="/problem/<?= $problem['id'] ?>" class="nav-link" role="tab">
				<i class="bi bi-journal-text"></i>
				<?= UOJLocale::get('problems::statement') ?>
			</a>
		</li>
		<li class="nav-item text-start">
			<a href="/problem/<?= $problem['id'] ?>/solutions" class="nav-link" role="tab">
				<i class="bi bi-journal-bookmark"></i>
				<?= UOJLocale::get('problems::solutions') ?>
			</a>
		</li>
		<li class="nav-item text-start">
			<a class="nav-link" href="/problem/<?= $problem['id'] ?>/statistics">
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
		评价：<?= getClickZanBlock('P', $problem['id'], $problem['zan']) ?>
	</div>
</div>

<?php uojIncludeView('sidebar', array()) ?>
</aside>

</div>

<?php echoUOJPageFooter() ?>
