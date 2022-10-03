<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
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

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-lg-9">
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="card card-default mb-2">
<div class="card-body">
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<h1 class="h2 card-title text-center">
<?php else: ?>
<h1 class="page-header text-center">
<?php endif ?>
	#<?=$problem['id']?>. <?=$problem['title']?> 管理
</h1>

<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">编辑</a></li>
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">管理者</a></li>
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">数据</a></li>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
	<li class="nav-item"><a class="nav-link" href="/problem/<?=$problem['id']?>" role="tab">返回</a></li>
<?php endif ?>
</ul>

<div class="mt-3 mb-2">
<p>提示：</p>
<ol>
<li>请勿引用不稳定的外部资源（如来自个人服务器的图片或文档等），以便备份及后期维护；</li>
<li>请勿在题面中直接插入大段 HTML 源码，这可能会破坏页面的显示，可以考虑使用 <a href="http://island205.github.io/h2m/">转换工具</a> 转换后再作修正；</li>
<li>图片上传推荐使用 <a href="https://smms.app" target="_blank">SM.MS</a> 图床，以免后续产生外链图片大量失效的情况。</li>
</ol>
</div>

<hr>

<?php $problem_editor->printHTML() ?>


<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
</div>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<aside class="col mt-3 mt-lg-0">

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
			<a class="nav-link active" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
				<i class="bi bi-sliders"></i>
				<?= UOJLocale::get('problems::manage') ?>
			</a>
		</li>
	</ul>
</div>

<?php uojIncludeView('sidebar', array()) ?>
</aside>

</div>

<script>
	$(document).ready(function() {
		$('.markdown-body table').each(function() {
			$(this).addClass('table table-bordered table-striped');
		});
	});
</script>
<?php endif ?>

<?php echoUOJPageFooter() ?>
