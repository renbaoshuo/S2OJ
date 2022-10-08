<?php
	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}

	$blogs_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'blogs',
		'cond' => "poster = '".UOJContext::userid()."' and is_hidden = 0",
		'tail' => 'order by post_time desc',
		'page_len' => 5
	));

	$all_tags = DB::selectAll("select distinct tag from blogs_tags where blog_id in (select id from blogs where $blogs_cond)");
	
	$REQUIRE_LIB['mathjax'] = '';
	if (isset($REQUIRE_LIB['bootstrap5'])) {
		$REQUIRE_LIB['hljs'] = '';
	} else {
		$REQUIRE_LIB['shjs'] = '';
	}
	?>
<?php echoUOJPageHeader(UOJContext::user()['username'] . '的博客') ?>

<div class="row">
	<div class="
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	col-lg-9
	<?php else: ?>
	col-md-9
	<?php endif ?>
	">
		<?php if ($blogs_pag->isEmpty()): ?>
		<div class="text-muted">此人很懒，什么博客也没留下。</div>
		<?php else: ?>
		<?php foreach ($blogs_pag->get() as $blog): ?>
			<?php echoBlog($blog, array('is_preview' => true)) ?>
		<?php endforeach ?>
		<?php endif ?>
		<div class="text-center">
		<?= $blogs_pag->pagination(); ?>
		</div>
	</div>
	<div class="
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	col-lg-3
	<?php else: ?>
	col-md-3
	<?php endif ?>">
		<img class="media-object img-thumbnail center-block" alt="<?= UOJContext::user()['username'] ?> Avatar" src="<?= HTML::avatar_addr(UOJContext::user(), 512) ?>" />
		<?php if (UOJContext::hasBlogPermission()): ?>
		<div class="btn-group d-flex mt-3">
			<a href="<?= HTML::blog_url(UOJContext::userid(), '/post/new/write') ?>" class="btn btn-primary">
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				<i class="bi bi-pencil-square"></i>
				<?php else: ?>
				<span class="glyphicon glyphicon-edit"></span>
				<?php endif ?>
				写新博客
			</a>
			<a href="<?= HTML::blog_url(UOJContext::userid(), '/slide/new/write') ?>" class="btn btn-primary">
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				<i class="bi bi-file-earmark-slides"></i>
				<?php else: ?>
				<span class="glyphicon glyphicon-edit"></span>
				<?php endif ?>
				写新幻灯片
			</a>
		</div>
		<?php endif ?>
		<div class="card border-info
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	mt-3
	<?php else: ?>
	top-buffer-sm
	<?php endif ?>">
			<div class="card-header bg-info">标签</div>
			<div class="card-body">
			<?php if ($all_tags): ?>
			<?php foreach ($all_tags as $tag): ?>
				<?php echoBlogTag($tag['tag']) ?>
			<?php endforeach ?>
			<?php else: ?>
				<div class="text-muted">暂无</div>
			<?php endif ?>
			</div>
		</div>
	</div>
</div>
<?php echoUOJPageFooter() ?>
