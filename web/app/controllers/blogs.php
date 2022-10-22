<?php
	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}
	?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>

<div class="row">

<!-- left col -->
<div class="col-lg-9">

<!-- title container -->
<div class="d-flex flex-wrap justify-content-between align-items-center">

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
</div>
<!-- end title container -->

<?php
echoLongTable(
	['id', 'poster', 'title', 'post_time', 'zan', 'is_hidden'],
	'blogs',
	isSuperUser($myUser) ? "1" : "is_hidden = 0 or poster = '{$myUser['username']}'",
	'order by post_time desc',
	<<<EOD
	<tr>
		<th width="60%">标题</th>
		<th width="20%">发表者</th>
		<th width="20%">发表日期</th>
	</tr>
EOD,
	function($blog) {
		echo '<tr>';
		echo '<td>';
		echo getBlogLink($blog['id']);
		if ($blog['is_hidden']) {
			echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
		}
		echo '</td>';
		echo '<td>' . getUserLink($blog['poster']) . '</td>';
		echo '<td>' . $blog['post_time'] . '</td>';
		echo '</tr>';
	},
	[
		'page_len' => 10,
		'div_classes' => ['card', 'my-3', 'table-responsive'],
		'table_classes' => ['table', 'uoj-table', 'mb-0'],
	]
);
	?>

</div>

<!-- right col -->
<aside class="col-lg-3 mt-3 mt-lg-0">
<?php uojIncludeView('sidebar') ?>
</aside>
<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
