<?php
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}

	function echoBlogCell($blog) {
		global $REQUIRE_LIB;

		echo '<tr>';
		if ($blog['is_hidden']) {
			echo '<td><span class="text-danger">[已隐藏]</span> ' . getBlogLink($blog['id']) . '</td>';
		} else {
			echo '<td>' . getBlogLink($blog['id']) . '</td>';
		}
		echo '<td>' . getUserLink($blog['poster']) . '</td>';
		echo '<td>' . $blog['post_time'] . '</td>';
		echo '</tr>';
	}
	$header = <<<EOD
	<tr>
		<th width="60%">标题</th>
		<th width="20%">发表者</th>
		<th width="20%">发表日期</th>
	</tr>
EOD;
	$config = array();
	$config['table_classes'] = array('table', 'table-hover');

	if (isset($REQUIRE_LIB['bootstrap5'])) {
		$config['div_classes'] = array('card', 'my-3', 'table-responsive');
		$config['table_classes'] = array('table', 'uoj-table', 'mb-0');
	}
	?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-lg-9">
<div class="d-flex flex-wrap justify-content-between">
<?php endif ?>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<?php if (Auth::check()): ?>
<div class="float-right">
	<div class="btn-group">
		<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-secondary btn-sm">我的博客首页</a>
		<a href="<?= HTML::blog_url(Auth::id(), '/post/new/write')?>" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-edit"></span> 写新博客</a>
	</div>
</div>
<?php endif ?>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<h1 class="h2">
	<?= UOJLocale::get("blogs overview") ?>
</h1>
<div class="text-end">
	<div class="btn-group">
		<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-secondary btn-sm">
			我的博客首页
		</a>
		<a href="<?= HTML::blog_url(Auth::id(), '/post/new/write')?>" class="btn btn-primary btn-sm">
			<i class="bi bi-pencil"></i>
			写新博客
		</a>
	</div>
</div>
<?php else: ?>
<h3>博客总览</h3>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
<?php endif ?>

<?php echoLongTable(array('id', 'poster', 'title', 'post_time', 'zan', 'is_hidden'), 'blogs', isSuperUser($myUser) ? "1" : "is_hidden = 0 or poster = '{$myUser['username']}'", 'order by post_time desc', $header, 'echoBlogCell', $config); ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<aside class="col mt-3 mt-lg-0">
<?php uojIncludeView('sidebar', array()) ?>
</aside>

</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
